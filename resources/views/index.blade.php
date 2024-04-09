@extends('statamic::layout')

@section('content')
    <!-- Header Content -->
    <section>
        <h1>Alt Cookies</h1>
        <p class="my-2">Manage Google's consent functionality and other scripts</p>
        <p class="text-sm">Please note: fields for cookies (such as "Necessary") are placed on the frontend as inputted here. Please double check and make sure what you put in here is correct and safe.</p>
    </section>
    <!-- End Header Content -->

    <div>
        <publish-form
            action="{{ cp_route('alt-cookies-addon.save') }}"
            :blueprint='@json($blueprint)'
            :meta='@json($meta)'
            :values='@json($values)'
        ></publish-form>
    </div>
@endsection
