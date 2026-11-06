<?php namespace AltDesign\AltCookiesAddon\Http\Controllers;

use AltDesign\AltCookiesAddon\Helpers\Data;
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

    // For saving CP page data
    public function save( Request $request)
    {
        $data = new Data('settings');

        // Set the fields etc
        $blueprint = $data->getBlueprint(true);
        $fields = $blueprint->fields()->addValues($request->all());
        $fields->validate();

        // Save the data
        $data->setAll($fields->process()->values()->except('addon_instructions')->toArray());

        return true;
    }
}
