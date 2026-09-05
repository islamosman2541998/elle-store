<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Guards against a form the browser refuses to submit.
 *
 * A number input whose stored value is not a multiple of its step fails the
 * browser's own constraint check, and Chrome then blocks the whole form -
 * silently, with the save button appearing to do nothing. Server-side tests
 * never see it, because Laravel validates by its own rules. So this test
 * reads the rendered markup the way a browser would.
 */
class AdminFormsSubmitTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        StoreSetting::flushCurrent();

        Auth::login(User::query()->firstOrFail());
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_the_store_settings_form_can_be_submitted_by_a_browser(): void
    {
        $component = Livewire::test(
            \App\Filament\Resources\StoreSettingResource\Pages\EditStoreSetting::class,
            ['record' => StoreSetting::query()->value('id')]
        );

        $offenders = $this->numberInputsTheBrowserWouldReject(
            $component->html(),
            $component->get('data') ?? []
        );

        $this->assertSame(
            [],
            $offenders,
            "These number inputs fail the browser's step check, which blocks the save button:\n"
                . implode("\n", $offenders)
        );
    }

    /**
     * Every number input whose current value breaks its own min/step rule.
     *
     * Livewire binds values with wire:model rather than a value attribute, so
     * the number has to come from the component's state, not the markup.
     *
     * @return array<int, string>
     */
    private function numberInputsTheBrowserWouldReject(string $html, array $data): array
    {
        preg_match_all('/<input\b[^>]*type="number"[^>]*>/i', $html, $matches);

        $offenders = [];

        foreach ($matches[0] as $tag) {
            $step = $this->attribute($tag, 'step');
            $model = $this->attribute($tag, 'wire:model');

            if ($step === null || $step === 'any' || $model === null) {
                continue;
            }

            $key = str_starts_with($model, 'data.') ? substr($model, 5) : $model;
            $value = data_get($data, $key);

            if ($value === null || $value === '' || ! is_numeric($value) || ! is_numeric($step) || (float) $step <= 0) {
                continue;
            }

            $min = (float) ($this->attribute($tag, 'min') ?? 0);
            $steps = ((float) $value - $min) / (float) $step;

            // The browser allows a tiny float wobble; anything more it rejects.
            if (abs($steps - round($steps)) > 1e-6) {
                $offenders[] = "  - {$key}: value {$value} is not a multiple of step {$step} from min {$min}";
            }
        }

        return $offenders;
    }

    private function attribute(string $tag, string $name): ?string
    {
        $pattern = '/\b' . preg_quote($name, '/') . '="([^"]*)"/i';

        return preg_match($pattern, $tag, $m) ? $m[1] : null;
    }
}
