<?php namespace AltDesign\AltCookiesAddon\Http\Controllers;

use Illuminate\Http\Request;

use Statamic\Filesystem\Manager;
use Statamic\Fields\BlueprintRepository;
use Statamic\Fields\Blueprint;

use AltDesign\AltCookiesAddon\Helpers\Data;

class AltCookiesController
{

    // For rendering the CP page
    public function index()
    {
        $data = new Data('settings');

        $blueprint = $data->getBlueprint(true);
        $fields = $blueprint->fields()->addValues($data->all())->preProcess();

        return view('alt-cookies::index', [
            'blueprint' => $blueprint->toPublishArray(),
            'values'    => $fields->values(),
            'meta'      => $fields->meta(),
        ]);
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
        $data->setAll($fields->process()->values()->toArray());

        return true;
    }
}
