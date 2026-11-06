<?php namespace AltDesign\AltCookiesAddon\Http\Controllers;

use AltDesign\AltCookiesAddon\Helpers\Data;
use AltDesign\AltCookiesAddon\Support\PolicyWriter;
use AltDesign\AltCookiesAddon\Support\ScanStore;
use Facades\Statamic\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Statamic\CP\PublishForm;

class AltCookiesController
{

    // For rendering the CP page
    public function index()
    {
        $data = new Data('settings');

        $blueprint = $data->getBlueprint(true);

        $this->addScanTab($blueprint);

        $fields = $blueprint->fields()->addValues($data->all())->preProcess();

        // Statamic >= V6
        if(intval(Str::before(Version::get(), '.')) >= 6) {
            return PublishForm::make($blueprint)
                ->title('Alt Cookies')
                ->icon('eye-slash')
                ->values($fields->values()->toArray())
                ->submittingTo(cp_route('alt-cookies-addon.save'), 'POST');
        // Statamic < V6
        } else {
            return view('alt-cookies::index', [
                'blueprint' => $blueprint->toPublishArray(),
                'values'    => $fields->values(),
                'meta'      => $fields->meta(),
            ]);
        }
    }

    /**
     * The scan report goes in a tab of its own on this page, rendered through the html
     * fieldtype. PublishForm takes everything from the blueprint, so a tab has to be a
     * field, and this is the one fieldtype that renders markup we hand it.
     *
     * @param  \Statamic\Fields\Blueprint  $blueprint
     * @return void
     */
    protected function addScanTab($blueprint): void
    {
        $results = app(ScanStore::class)->get();

        $blueprint->ensureFieldInTab('scan_report', [
            'type' => 'html',
            'display' => 'Scan',
            'hide_display' => true,
            'sanitize' => false,
            'html' => view('alt-cookies::scan-panel', [
                'results' => $results,
                'policyMarkdown' => $results ? app(PolicyWriter::class)->markdown($results) : null,
                'policyHtml' => $results ? app(PolicyWriter::class)->html($results) : null,
            ])->render(),
        ], 'scan');
    }

    // For saving CP page data
    public function save( Request $request)
    {
        $data = new Data('settings');

        // Set the fields etc
        $blueprint = $data->getBlueprint(true);
        $fields = $blueprint->fields()->addValues($request->all());
        $fields->validate();

        // Save the data
        $values = $fields->process()->values()->except(['addon_instructions', 'scan_report'])->toArray();

        // Statamic shows a field's default in place of null, so a cleared name would come
        // back on the next save. An empty string stays empty and means no event.
        $values['consent_event_name'] ??= '';

        $data->setAll($values);

        return true;
    }
}
