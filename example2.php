<?php namespace TrendLive\Http\Controllers;
use Illuminate\Support\Facades\View;
use TrendLive\Http\Requests;
use TrendLive\Http\Controllers\Controller;
use TrendLive\Collection;
use Illuminate\Http\Request;
class IndexController extends Controller {
    public function index(){
        $liders = Collection::get_random_collections(6);
        foreach($liders as $lider) $lider-> collection_name = mb_strimwidth($lider-> collection_name, 0, 80, "...");
        reset($liders);
        $interesteds = Collection::get_random_collections(6);
        foreach($interesteds as $interested) $interested-> collection_name = mb_strimwidth($interested-> collection_name, 0, 80, "...");
        reset($interesteds);
        $url =$_ENV['URL_CURRENT'];
        return view('Index',compact('liders','interesteds','url'));
    }
}
