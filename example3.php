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
    public function index(){//��������� �������� �������
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
    public function get_login(){//�������� �����������
        if(Auth::check())
            return redirect('/profile/my_collection');
        return view('Login');
    }
    public function get_recovery_password(){//�������� �������������� ������
        if(Auth::check())
            return redirect('/profile/my_collection');
        return view('Recovery_password');
    }
    public  function post_recovery_password(RecoveryPasswordFormRequest $request){//�� ���� �������� ������ ������
        $data = $request->all();//�������� ��� ������
        $email = $data['email'];//����������� email
        $new_password = Str::random(6);//��������� ������ �� 6 ��������
        $result = User::set_new_password($email,$new_password);//��������� ������ ������
        if($result){//���� ������������ ���������� � ������ ������
            $user = User::get_user_for_email($email);//�������� ������ ������������
            $to_mail = [//��������� ������ ������
                'password' => $new_password
            ];
            Mail::send('emails.recovery_password', $to_mail, function($message) use ($user){//���������� ������
                $message->to($user->email, '������������')->subject('�������������� ������');//��������� �������� � ���� ������
            });
            return redirect('/recovery_password')->with('success','�� ������� ������������ ������. ����� ������ ������ �� ��� E-mail.');//��������� �� ������
        }
        else //���� ������������ �� ����������
            return redirect('/recovery_password')->withErrors('������������ � ����� E-mail �� ����������.');//��������� �� ������

    }
    public  function post_login(LoginFormRequest $request){//����������� ����� post
        $data = $request->all();
        $email = $data['email'];//�������� email
        $active = User::check_active($email);//��������� ������ ���������
        if($active){
            $remember = false; // �� ���������� ������������
            if(isset($data['remember']))
                $remember = true; // ���������� ������������
            $user = User::login($data,$remember);//��������������
            if($user instanceof Model)//������������ ���������������
                return redirect('/profile/my_collection');
            else//����������� �� �������
                return view('Login')->withErrors('��������� ������ �� �����, ���������� ������.');
        }
        else
            return view('Login')->withErrors('��� ����� � ������ ������� ���������� ����������� ����� ����������� �����');
    }
    public function logout(){//����� �� ����������
        if(Auth::check())
            Auth::logout();
        return redirect('/login');
    }
    public function get_change_password(){
        if(!Auth::check())
            return redirect('/login');
        return view('Change_password');
    }
    public static function post_change_password(ChangePasswordFormRequest $request){//����� ������ ���������
        $data = $request->all();//�������� ��� ������
        $user = Auth::user();//��������� ������ ��������������� ������������
        $result = User::set_new_password($user->email,$data['password']);//��������� ������ ������
        if($result){//���� ������ ������� ����������
            $to_mail = [//��������� ������ ������
                'password' => $data['password'],
                'email' => $user->email
            ];
            Mail::send('emails.change_password', $to_mail, function($message) use ($user){//���������� ������
                $message->to($user->email, '������������')->subject('����� ������');//��������� �������� � ���� ������
            });
            return redirect('/profile/change_password')->with('success','�� ������� ������� ������. ������ ��� ����� � ������� ���������� �� ��� E-mail .');//��������� �� ������
        }
        else //���� ������������ �� ����������
            return redirect('/profile/change_password')->withErrors('� ������ ������ ���������� ������� ������. ���������� �����.');//��������� �� ������
    }
}