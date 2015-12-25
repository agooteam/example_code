<?php namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginFormRequest;
use Auth;
use App\User;
class AdminAuthController extends AdminController {

    public function login(){//������ �����������
        if(Auth::check()) return redirect('/admin');//���� ������������� ��� ����������� , ������������ �� ������ ��������
        return view('AdminLogin');//���������� ����� �����������
    }

    public function login_post(AdminLoginFormRequest $request){//������ �����������
        $data = $request->all();//�������� post ������ http �������
        $email = $data['email'];//�������� email
        $admin = User::check_admin($email);//��������� ����� ��������������
        if($admin){
            $remember = false; // �� ���������� ������������
            if(isset($data['remember']))
                $remember = true; // ���������� ������������
            $user = User::login($data,$remember);
            if($user instanceof User)//������������ ���������������, �������� ������ ������������
                return redirect('/admin');
            else//����������� �� �������
                return view('AdminLogin')->withErrors('��������� ������ �� �����, ���������� ������.');
        }
        else
            return view('AdminLogin')->withErrors('� ������� ����� ������� ������ ���������!');

    }

    public function logout(){//����� �� �������
        if(Auth::check()) Auth::logout();
        return redirect('/admin/login');
    }

}