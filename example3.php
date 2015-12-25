<?php namespace TrendLive\Http\Controllers;
use Illuminate\Support\Facades\URL;
use TrendLive\Collection;
use TrendLive\Http\Requests;
use TrendLive\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use TrendLive\User;
use Illuminate\Support\Str;
use TrendLive\Http\Requests\RecoveryPasswordFormRequest;
use TrendLive\Http\Requests\ChangePasswordFormRequest;
use TrendLive\Http\Requests\LoginFormRequest;
use Illuminate\Database\Eloquent\Model;

class ProfileController extends Controller {
    public function profile(){
        if(Auth::check()) return redirect('/profile/my_collection');
        else return redirect('/login');
    }
    public function index(){//—тартова€ страница профил€
        $pagination = 6;
        $page = 1;
        $url =  URL::full();
        if(isset($_GET['page']))
            $page = $_GET['page'];
        else if(substr_count( $url , '?') != 0 )
            abort(404);
        if(!Auth::check())
            return redirect('/login');
        $collections = Collection::get_my_collection(Auth::user()->id,$pagination);
        if(($page < 1 || $page > $collections-> lastPage() && $collections-> total() != 0 ) )
            abort(404);
        foreach($collections as $collection){
            $collection-> collection_name = mb_strimwidth($collection-> collection_name, 0, 70, " ...");
            $collection-> description = mb_strimwidth($collection-> description, 0, 120, " ...");
        }
        reset($collections);
        return view('Profile',compact('collections'));
    }
    public function get_login(){//страница авторизации
        if(Auth::check())
            return redirect('/profile/my_collection');
        return view('Login');
    }
    public function get_recovery_password(){//страница восстановлени€ парол€
        if(Auth::check())
            return redirect('/profile/my_collection');
        return view('Recovery_password');
    }
    public  function post_recovery_password(RecoveryPasswordFormRequest $request){//на вход подаетс€ чистый запрос
        $data = $request->all();//получаем все данные
        $email = $data['email'];//вытаскиваем email
        $new_password = Str::random(6);//генераци€ строки из 6 символов
        $result = User::set_new_password($email,$new_password);//установка нового парол€
        if($result){//если пользователь существует и пароль сменен
            $user = User::get_user_for_email($email);//получаем модель пользовател€
            $to_mail = [//формируем массив данных
                'password' => $new_password
            ];
            Mail::send('emails.recovery_password', $to_mail, function($message) use ($user){//отправл€ем письмо
                $message->to($user->email, 'ѕользователь')->subject('¬осстановление парол€');//указываем адресата и тему письма
            });
            return redirect('/recovery_password')->with('success','¬ы успешно восстановили пароль. Ќовый пароль выслан на ваш E-mail.');//сообщение об успехе
        }
        else //если пользовател€ не существует
            return redirect('/recovery_password')->withErrors('ѕользовател€ с таким E-mail не существует.');//сообщение об ошибке

    }
    public  function post_login(LoginFormRequest $request){//јвторизаци€ метод post
        $data = $request->all();
        $email = $data['email'];//получаем email
        $active = User::check_active($email);//провер€ем статус активации
        if($active){
            $remember = false; // не запоминать пользовател€
            if(isset($data['remember']))
                $remember = true; // запоминать пользовател€
            $user = User::login($data,$remember);//авторизируемс€
            if($user instanceof Model)//пользователь авторизировалс€
                return redirect('/profile/my_collection');
            else//авторизаци€ не удалась
                return view('Login')->withErrors('¬веденные данные не верны, попробуйте заново.');
        }
        else
            return view('Login')->withErrors('ƒл€ входа в личный кабинет необходимо подтвердить адрес электронной почты');
    }
    public function logout(){//выход из приложени€
        if(Auth::check())
            Auth::logout();
        return redirect('/login');
    }
    public function get_change_password(){
        if(!Auth::check())
            return redirect('/login');
        return view('Change_password');
    }
    public static function post_change_password(ChangePasswordFormRequest $request){//смена парол€ обработка
        $data = $request->all();//получаем все данные
        $user = Auth::user();//получение модели авторизованного пользовател€
        $result = User::set_new_password($user->email,$data['password']);//установка нового парол€
        if($result){//если пароль успешно установлен
            $to_mail = [//формируем массив данных
                'password' => $data['password'],
                'email' => $user->email
            ];
            Mail::send('emails.change_password', $to_mail, function($message) use ($user){//отправл€ем письмо
                $message->to($user->email, 'ѕользователь')->subject('—мена парол€');//указываем адресата и тему письма
            });
            return redirect('/profile/change_password')->with('success','¬ы успешно сменили пароль. ƒанные дл€ входа в профиль отправлены на ваш E-mail .');//сообщение об успехе
        }
        else //если пользовател€ не существует
            return redirect('/profile/change_password')->withErrors('¬ данный момент невозможно сменить пароль. ѕопробуйте позже.');//сообщение об успехе
    }
}