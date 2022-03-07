<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\NewsResource;
use App\Models\News;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Input\Input;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function index()
    // {
    //     $data = News::limit(100)->get();
    //     return response()->json(NewsResource::collection($data));
    // }

    public function index(Request $request) {
        $this->validate($request, [
            'pageSize' => 'numeric|min:5',
            'page' => 'numeric'
        ]);
        $page = $request->has('page') ? (int)$request->get('page') : 1;
        $pageSize = $request->has('pageSize') ? (int)$request->get('pageSize') : 5;
        $rows = $pageSize;
        $offset = ($page * $rows) - $rows;
        $title = $request->get('title');
        $category = $request->get('category');
        $pubdate_from = $request->get('pubdate_from');
        $pubdate_to = $request->get('pubdate_to');
        $pubdate = $request->get('pubdate');
        $publisher = $request->get('publisher');

        $news_list = DB::connection('mysql_news')->table('news_bulk')->select(
            'news_bulk.author',
            'news_bulk.category_source',
            'news_bulk.content',
            'merchant.country_id',
            'countries.name AS country_name',
            'news_bulk.image_url AS cover',
            'news_bulk.created_at',
            'news_summary.description',
            'news_bulk.exclusive',
            'merchant.ga_id AS ga_partner_id',
            'merchant.google_index',
            'news_bulk.id',
            (DB::raw('IFNULL(LOWER(news_image.image), "-") AS image')),
            'news_bulk.is_headline',
            'news_bulk.link',
            'news_meta.description AS meta_description',
            'news_meta.keyword AS meta_keyword',
            'news_meta.title AS meta_title',
            'news_bulk.permalink',
            'news_pinned.pinned',
            (DB::raw('UNIX_TIMESTAMP(news_bulk.pubdate) AS pubDate')),
            'news_bulk.rss_id',
            'news_bulk.sorting',
            'news_bulk.source',
            'news_bulk.category_id AS subcategory_id',
            'category.name AS subcategory_name',
            'news_bulk.tags',
            'news_bulk.title',
            'news_likes.count AS total_like',
            'news_views.count AS total_views',
            'news_views.updated_at'
            )
        ->leftJoin('news_image','news_image.news_id','=','news_bulk.id')
        ->leftJoin('news_summary','news_summary.news_id','=','news_bulk.id')
        ->leftJoin('news_views','news_views.news_id','=','news_bulk.id')
        ->leftJoin('news_likes','news_likes.news_id','=','news_bulk.id')
        ->leftJoin('news_meta','news_meta.news_id','=','news_bulk.id')
        ->leftJoin('news_pinned','news_pinned.news_id','=','news_bulk.id')
        ->leftJoin('merchant','merchant.name','=','news_bulk.source')
        ->leftJoin('category','category.id','=','news_bulk.category_id')
        ->leftJoin('countries','countries.id','=','merchant.country_id')
        ->where([
            'news_bulk.publish' => 1
        ])
        ->when($title, function ($query) use ($title) {
                    return $query->where('news_bulk.title', '=', $title);
                })
        ->when($category, function ($query) use ($category) {
                    return $query->where('category.name', '=', $category);
                })
        ->when($publisher, function ($query) use ($publisher) {
                    return $query->where('news_bulk.author', '=', $publisher);
                })
        ->when($pubdate_from, function ($query) use ($pubdate_from) {
                    return $query->where('news_bulk.pubdate', '>=', date('Y-m-d H:i:s', $pubdate_from));
                })
        ->when($pubdate_to, function ($query) use ($pubdate_to) {
                    return $query->where('news_bulk.pubdate', '<=', date('Y-m-d H:i:s', $pubdate_to));
                })
        ->when($pubdate, function ($query) use ($pubdate) {
                    return $query->where('news_bulk.pubdate', '=', date('Y-m-d H:i:s', $pubdate));
                })
        ->skip($offset)->take($rows);

        $get_news = $news_list->get();
        $news = $get_news->toArray();
        $total = $news_list->count();

        $pages =  (object) [
            "current_page" => $page,
            "per_page" => $rows,
            "total" => $total,
            "total_page" =>  (int)ceil($total / $rows)
        ];

        $meta = (object) [
            'image_path' => env('ASSET_URL'),
            'pagination' => $pages
        ];

        if (is_object($meta)) $results['meta'] = $meta;
        $results = [
            'data' => $news,
            'meta' => $meta,
            'status' => (object) [
                "code" => 0,
                "message_client" => "Success",
                "message_server" => "Success"
            ]
        ];

        return response()->json($results);
    }

}
