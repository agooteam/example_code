<?php namespace TrendLive\Http\Controllers;
use TrendLive\Http\Requests;
use TrendLive\Http\Controllers\Controller;
use Auth;
use TrendLive\Http\Requests\SaveCollectionFormRequest;
use TrendLive\Http\Requests\SaveVideoFormRequest;
use Carbon\Carbon;
use File;
use Image;
use Illuminate\Http\Request;
use TrendLive\Category;
use TrendLive\Collection;
use TrendLive\Video;
use Illuminate\Database\Eloquent\Model;

class CollectionsController extends Controller {
    public static function get_new_collection(){
        if(!Auth::check())
            return redirect('/login');
        $categories = Category::all();
        return view('New_collection',compact('categories'));
    }
    public function post_new_collection(SaveCollectionFormRequest $request ){//сохранение нового курса
        if(!Auth::check())
            return redirect('/login');
        $data = $request->all();
        $collection_name = $data['collection_name'];//получаем название курса
        $description = $data['description'];//получаем описание курса
        $category_id = $data['category'];//получаем категорию курса
        $url = $request->url();//получаем путь
        $pos = strrpos($url, 'profile');//обрезаем
        $url = substr($url,0,$pos);//получаем домен + протокол
        $UploadPathImage = public_path().'/assets/temp/';//временный
        $image_url  = null;//может быть и не будет загружно фотографии
        if($request->hasFile('image')){
            $extension = $data['image']->getClientOriginalExtension();
            $time = Carbon::now();
            $time->toDateTimeString();
            $user_id = Auth::user()->id;
            $image_name = 'image_'.md5($user_id.'_'.$time).'.'.mb_strtolower($extension);
            $data['image']-> move($UploadPathImage, $image_name);
            $image_url = $url.'assets/temp/'.$image_name;
        }
        $data = [
            'category_id' => $category_id,
            'user_id' => Auth::user()->id,
            'collection_name' => $collection_name,
            'description' => $description,
            'image_url' => $image_url,
        ];
        $collection_id = Collection::save_collection($data);//сохраняем курс и получаем id
        return redirect('/profile/collection/edit/'.$collection_id);
    }
    public function get_collection_edit($collection_id = null){
        if($collection_id != null && !ctype_digit($collection_id))
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        if($collection_id == null)
            return  redirect('/profile/my_collection');
        $collection = Collection::get_collection($collection_id);
        $user_id = Auth::user()-> id;// id пользователя
        if($collection -> user_id != $user_id)
            return redirect('/profile/my_collection');
        if($collection-> image_url != null && $collection-> image_preview_url == null){//курс впервые редактируется
            $temp_url = $collection-> image_url;//получение временного пути
            $delete_pos = strrpos($temp_url, 'image');//получаем позицию
            $image_name = substr($temp_url,$delete_pos,strlen($temp_url));//получаем старое имя
            $pos = strrpos($temp_url,'assets');//получаем позицию
            $url = substr($temp_url,0,$pos);//получаем протокол + домен
            $extension_pos = strrpos($image_name,'.');//получаем позицию
            $extension = substr($image_name,$extension_pos,strlen($image_name));//получаем расширение
            $UploadPathTemp = public_path().'/assets/temp/';//путь темповой папки
            $UploadPathImage = public_path().'/assets/collections/';//путь папки избражения
            $UploadPathImagePreview = public_path().'/assets/collections/preview/';//путь папки превью
            $time = Carbon::now()->format('Y_m_d_h_m_s');
            $image_name_new = 'image_collection_'.$collection -> id.'_'.$time.mb_strtolower($extension);//Новое имя
            File::copy($UploadPathTemp.$image_name,$UploadPathImage.$image_name_new);//копируем файл
            File::copy($UploadPathTemp.$image_name,$UploadPathImagePreview.$image_name_new);//копируем файл
            Image::make(sprintf($UploadPathImage.'%s', $image_name_new))->resize(180, 224)->save();//меняем размер
            Image::make(sprintf($UploadPathImagePreview.'%s', $image_name_new))->resize(200, 185)->save();//меняем рамер
            File::delete($UploadPathTemp.$image_name);//удаляем временное изображение
            $data = [//подготавливаем массив для обновления
                'image_url' => $url.'assets/collections/'.$image_name_new,
                'image_preview_url' => $url.'assets/collections/preview/'.$image_name_new
            ];
            Collection::update_collection($collection_id,$data);//обновляем данные курса
        }
        $categories = Category::all();
        $videos = Video::get_video($collection_id);
        $i = 1;
        foreach($videos as $video){
            $video-> video_name = mb_strimwidth($video-> video_name, 0, 40, "...");
            $video-> video_name = $i.'. '.$video-> video_name;
            $i++;
        }
        reset($videos);
        $collection = Collection::get_collection($collection_id);
        return  view('Collection_edit',compact('categories','collection','videos'));
    }
    public function post_collection_edit($collection_id , SaveCollectionFormRequest $request){
        if($collection_id != null && !ctype_digit($collection_id))
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        $data = $request->all();
        $collection_name = $data['collection_name'];//получаем название курса
        $description = $data['description'];//получаем описание курса
        $category_id = $data['category'];//получаем категорию курса
        $url = $request->url();//получаем путь
        $pos = strrpos($url, 'profile');//обрезаем
        $url = substr($url,0,$pos);//получаем домен + протокол
        $collection_old = Collection::where('id',$collection_id)->get();
        $i_url =  $collection_old[0]-> image_url;
        $i_purl = $collection_old[0]-> image_preview_url;
        if($request->hasFile('image')){
            if($i_url != null && $i_purl != null){//Если ранее были загружены изображения
                $delete_pos = strrpos($i_url, 'image');
                $delete_image_name = substr($i_url,$delete_pos,strlen($i_url));
                $UploadPathImage = public_path().'/assets/collections/';
                $UploadPathImagePreview = public_path().'/assets/collections/preview/';
                File::delete($UploadPathImage.$delete_image_name);
                File::delete($UploadPathImagePreview.$delete_image_name);
            }
            $UploadPathImage = public_path().'/assets/collections/';
            $UploadPathImagePreview = public_path().'/assets/collections/preview/';//временный
            $extension = $data['image']->getClientOriginalExtension();
            $time = Carbon::now()->format('Y_m_d_h_m_s');
            $image_name = 'image_collection_'.$collection_id.'_'.$time.'.'.mb_strtolower($extension);
            $data['image']-> move($UploadPathImage, $image_name);
            File::copy($UploadPathImage.$image_name,$UploadPathImagePreview.$image_name);//копируем файл
            Image::make(sprintf(public_path().'/assets/collections/%s', $image_name))->resize(180, 224)->save();//меняем размер
            Image::make(sprintf(public_path().'/assets/collections/preview/%s', $image_name))->resize(200, 185)->save();//меняем размер
            $i_url = $url.'assets/collections/'.$image_name;
            $i_purl = $url.'assets/collections/preview/'.$image_name;
        }
        $data = [
            'category_id' => $category_id,
            'collection_name' => $collection_name,
            'description' => $description,
            'image_url' => $i_url,
            'image_preview_url' => $i_purl
        ];
        Collection::update_collection($collection_id,$data);
        return redirect('profile/collection/edit/'.$collection_id)->with('success','Данные успешно сохранены');
    }
    public function delete_collection($collection_id){
        if($collection_id != null && !ctype_digit($collection_id))
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        $user_id = Auth::user()-> id;
        $collection = Collection::where('id',$collection_id)->get();
        $user_collection = $collection[0]-> user_id;
        if($user_collection == $user_id && $collection[0] instanceof Collection){
            $image_url = $collection[0] -> image_url;
            $image_preview_url = $collection[0] -> image_preview_url;
            if($image_url != null && $image_preview_url != null){
                $delete_pos = strrpos($image_url, 'image');
                $delete_image_name = substr($image_url,$delete_pos,strlen($image_url));
                $UploadPathImage = public_path().'/assets/collections/';
                $UploadPathImagePreview = public_path().'/assets/collections/preview/';
                File::delete($UploadPathImage.$delete_image_name);
                File::delete($UploadPathImagePreview.$delete_image_name);
            }
            Video::delete_collection_video($collection_id);
            Collection::delete_collection($collection_id);
        }
        return redirect('/profile');
    }
    public function get_new_video($collection_id = null){
        if($collection_id != null && !ctype_digit($collection_id))
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        if($collection_id == null)
            return  redirect('/profile/my_collection');
        $collection = Collection::get_collection($collection_id);
        if(!$collection instanceof Collection)
            return redirect('/profile/my_collection');
        $user_id = Auth::user()-> id;// id пользователя
        if($collection -> user_id != $user_id)
            return redirect('/profile/my_collection');
        return view('New_video',compact('collection_id'));
    }
    public function post_new_video($collection_id = null, SaveVideoFormRequest $request){
        if($collection_id != null && !ctype_digit($collection_id))
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        if($collection_id == null)
            return  redirect('/profile/my_collection');
        $collection = Collection::get_collection($collection_id);
        $user_id = Auth::user()-> id;
        if($collection -> user_id != $user_id)
            return redirect('/profile/my_collection');
        $input = $request->all();
        $youtube_link = substr($input['youtube_link'],17,strlen($input['youtube_link']));
        $search_list_param = substr_count( $youtube_link , '?list=');
        if($search_list_param > 0){
            $pos = strrpos($youtube_link, '?list=');
            $youtube_link = substr($youtube_link,0,$pos);
        }
        $data = [
            'video_name' => $input['video_name'],
            'youtube_link' => $youtube_link,
            'collection_id' => $collection_id
        ];
        Video::save_video($data);
        Collection::update_collection($collection_id,['count_videos' => $collection-> count_videos + 1]);
        return redirect('profile/collection/edit/'.$collection_id);
    }
    public function get_edit_video($video_id = null){
        if($video_id != null && !ctype_digit($video_id))
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        if($video_id == null)
            return  redirect('/profile/my_collection');
        $video = Video::get_video_by_id($video_id);
        if(!$video instanceof Video)
            return redirect('/profile/my_collection');
        $collection_id = $video-> collection_id;
        $collection = Collection::get_collection($collection_id);
        $user_id = Auth::user()-> id;
        if($collection -> user_id != $user_id)
            return redirect('/profile/my_collection');
        return view('Video_edit', compact('video','collection_id'));
    }
    public function post_edit_video($video_id = null,SaveVideoFormRequest $request){
        if($video_id != null && !ctype_digit($video_id))
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        if($video_id == null)
            return  redirect('/profile/my_collection');
        $video = Video::get_video_by_id($video_id);
        if(!$video instanceof Video)
            return redirect('/profile/my_collection');
        $collection_id = $video-> collection_id;
        $collection = Collection::get_collection($collection_id);
        $user_id = Auth::user()-> id;
        if($collection -> user_id != $user_id)
            return redirect('/profile/my_collection');
        $input = $request->all();
        $youtube_link = substr($input['youtube_link'],17,strlen($input['youtube_link']));
        $search_list_param = substr_count( $youtube_link , '?list=');
        if($search_list_param > 0){
            $pos = strrpos($youtube_link, '?list=');
            $youtube_link = substr($youtube_link,0,$pos);
        }
        $data = [
            'video_name' => $input['video_name'],
            'youtube_link' => $youtube_link
        ];
        Video::update_video($video_id,$data);
        return redirect('profile/video/edit/'.$video_id)->with('success','Данные успешно сохранены');
    }
    public function delete_video($video_id){
        if($video_id != null && !ctype_digit($video_id))
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        if($video_id == null)
            return  redirect('/profile/my_collection');
        $video = Video::get_video_by_id($video_id);
        if(!$video instanceof Video)
            return redirect('/profile/my_collection');
        $collection_id = $video-> collection_id;
        $collection = Collection::get_collection($collection_id);
        $user_id = Auth::user()-> id;
        if($collection -> user_id != $user_id)
            return redirect('/profile/my_collection');
        Video::delete_video($video_id);
        Collection::update_collection($collection_id,['count_videos' => $collection-> count_videos - 1]);
        return redirect('/profile/collection/edit/'.$collection_id);
    }
}
