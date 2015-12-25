<?php namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginFormRequest;
use Auth;
use App\User;
class AdminAuthController extends AdminController {

    public function login(){//Фунция авторизации
        if(Auth::check()) return redirect('/admin');//если администратор уже авторизован , переадресуем на другую страницу
        return view('AdminLogin');//показываем форму авторизации
    }

    public function login_post(AdminLoginFormRequest $request){//Фунция авторизации
        $data = $request->all();//получаем post данные http запроса
        $email = $data['email'];//получаем email
        $admin = User::check_admin($email);//проверяем права администратора
        if($admin){
            $remember = false; // не запоминать пользователя
            if(isset($data['remember']))
                $remember = true; // запоминать пользователя
            $user = User::login($data,$remember);
            if($user instanceof User)//пользователь авторизировался, получили модель пользователя
                return redirect('/admin');
            else//авторизация не удалась
                return view('AdminLogin')->withErrors('Введенные данные не верны, попробуйте заново.');
        }
        else
            return view('AdminLogin')->withErrors('В админку могут попасть только избранные!');

    }

    public function logout(){//выход из системы
        if(Auth::check()) Auth::logout();
        return redirect('/admin/login');
    }

}