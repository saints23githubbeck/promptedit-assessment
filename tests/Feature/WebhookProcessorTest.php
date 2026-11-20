<?php
namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Models\CreditLedgerEntry;
use App\Models\Generation;
use App\Models\User;
use App\Services\Money;
use App\Services\Pixelforge\WebhookProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookProcessorTest extends TestCase
{
    use RefreshDatabase;

    private function generation(): Generation
    {
        $user=User::create(['name'=>'Test User','email'=>'test@example.com','password'=>'password']);
        return Generation::create([
            'user_id'=>$user->id,'provider_id'=>'gen_test_123','idempotency_key'=>(string)\Illuminate\Support\Str::uuid(),
            'prompt'=>'A test image','size'=>'1024x1024','quality'=>'medium','reference_images'=>[],
            'status'=>'queued','rates'=>['input_text_tokens'=>'5.00','input_image_tokens'=>'10.00','output_tokens'=>'40.00','reasoning_tokens'=>'12.50'],
            'usage_estimate'=>['input_text_tokens'=>1,'input_image_tokens'=>0,'output_tokens'=>1100,'reasoning_tokens'=>1000],
            'estimated_provider_cost_units'=>0,'estimated_customer_cost_units'=>0,
        ]);
    }

    private function event(Generation $generation,string $id,string $status):array
    {
        return ['id'=>$id,'object'=>'event','type'=>'generation.'.$status,'created_at'=>now()->toIso8601String(),'data'=>['object'=>[
            'id'=>$generation->provider_id,'object'=>'generation','status'=>$status,
            'rates'=>$generation->rates,'metadata'=>['local_generation_id'=>(string)$generation->id],
            'usage'=>$status==='succeeded'?['input_text_tokens'=>10,'input_image_tokens'=>0,'output_tokens'=>1100,'reasoning_tokens'=>200]:null,
            'image_url'=>$status==='succeeded'?'https://example.test/image.png':null,
            'completed_at'=>$status==='succeeded'?now()->toIso8601String():null,
            'error'=>$status==='failed'?['code'=>'generation_failed','message'=>'Failed for test']:null,
        ]]];
    }

    public function test_duplicate_success_webhook_is_charged_once():void
    {
        $generation=$this->generation();$event=$this->event($generation,'evt_duplicate','succeeded');$processor=app(WebhookProcessor::class);
        $processor->process($event);$processor->process($event);
        $this->assertSame(GenerationStatus::Succeeded,$generation->fresh()->status);
        $this->assertSame(1,CreditLedgerEntry::where('generation_id',$generation->id)->count());
        $this->assertSame(Money::withMarkup($generation->fresh()->actual_provider_cost_units),$generation->fresh()->actual_customer_cost_units);
    }

    public function test_late_processing_event_cannot_regress_a_successful_generation():void
    {
        $generation=$this->generation();$processor=app(WebhookProcessor::class);
        $processor->process($this->event($generation,'evt_success','succeeded'));
        $processor->process($this->event($generation,'evt_late_processing','processing'));
        $this->assertSame(GenerationStatus::Succeeded,$generation->fresh()->status);
        $this->assertSame(1,CreditLedgerEntry::where('generation_id',$generation->id)->count());
    }

    public function test_failed_generation_has_no_charge():void
    {
        $generation=$this->generation();app(WebhookProcessor::class)->process($this->event($generation,'evt_failed','failed'));
        $this->assertSame(GenerationStatus::Failed,$generation->fresh()->status);
        $this->assertSame(0,CreditLedgerEntry::where('generation_id',$generation->id)->count());
        $this->assertNull($generation->fresh()->actual_customer_cost_units);
    }

    public function test_signature_uses_timestamp_and_raw_body():void
    {
        config(['services.pixelforge.webhook_secret'=>'whsec_test','services.pixelforge.webhook_tolerance'=>300]);
        $timestamp=time();$body='{"id":"evt_test","object":"event","type":"ping"}';
        $signature=hash_hmac('sha256',$timestamp.'.'.$body,'whsec_test');
        $this->assertTrue(app(WebhookProcessor::class)->verify($body,"t={$timestamp},v1={$signature}"));
        $this->assertFalse(app(WebhookProcessor::class)->verify($body.' ',"t={$timestamp},v1={$signature}"));
        $this->assertFalse(app(WebhookProcessor::class)->verify($body,"t=".($timestamp-1000).",v1={$signature}"));
    }
    public function test_webhook_received_before_create_response_is_replayed_after_provider_id_is_linked(): void
    {
        $generation = $this->generation();
        $generation->update(['provider_id' => null]);

        $event = $this->event($generation, 'evt_before_create_response', 'succeeded');
        $event['data']['object']['id'] = 'gen_test_123';
        app(WebhookProcessor::class)->process($event);

        $this->assertSame(GenerationStatus::Succeeded, $generation->fresh()->status);
        $this->assertSame('gen_test_123', $generation->fresh()->provider_id);
        $this->assertNotNull(\App\Models\PixelforgeEvent::where('event_id', 'evt_before_create_response')->first()->processed_at);
        $this->assertSame(1, CreditLedgerEntry::where('generation_id', $generation->id)->count());

        // The later POST response and replay are harmless after the event was applied.
        $generation->update(['provider_id' => 'gen_test_123']);
        app(WebhookProcessor::class)->replayPending('gen_test_123');
        $this->assertSame(1, CreditLedgerEntry::where('generation_id', $generation->id)->count());
    }

    public function test_queued_event_cannot_regress_processing_generation(): void
    {
        $generation = $this->generation();
        $generation->update(['status' => 'processing']);
        $event = $this->event($generation, 'evt_late_queued', 'queued');

        app(WebhookProcessor::class)->process($event);

        $this->assertSame(GenerationStatus::Processing, $generation->fresh()->status);
    }

}
