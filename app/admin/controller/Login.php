<?php
namespace app\admin\controller;

use app\common\controller\Common;
use think\Db;
use think\Session;
use think\Validate;

/**
 * 登录
 */
class Login extends Common
{
    function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub

        if ($this->is_login()) {
            $this->redirect('admin/Index/index');
        }
    }

    function show_login()
    {
        return view('login_page');
    }

    //登陆判断
    function login_in()
    {

        $validate = new Validate([
            ['username', 'require', '用户名必须填写'],
            ['password', 'require', '密码必须填写'],
            ['luotest_response', 'require', '请先验证'],
        ]);

        if (!$validate->check($_POST)) {
            $this->error($validate->getError());
        }

        $luo_res = self::luosimao_respons(input('luotest_response'));

        if (!empty($luo_res['error'])) {
            $this->error('验证无效,请重试');
        }

        $users=Db::name('users')
            ->where('user_login', input('username'))
            ->find();

        //判断用户是否存在
        if ($users&&$users['user_status']!=0) {

            $inp_pass = encrypt_password(input('password'), $users['user_pass_salt']);//输入密码转义
            $is_sure=$users['user_pass'] == $inp_pass;//密码比对

            //判断密码是否相同
            if ($is_sure) {

                $data_sign = [
                    'id'=>$users['id'],
                    'last_login_time'=>time(),
                    'last_login_ip' => request()->ip(),
                ];
                $save_users = $data_sign;
                $save_users['user_hits'] = $users['user_hits'] + 1;

                if (Db::name('users')->update($save_users)) {
                    cookie('UID', set_secret($users['id']),604800);
                    session('SUID', set_secret($users['id']));
                    session('TOKEN_LOGIN', data_signature($data_sign));

                    $mess = $users['user_login'] . "第" . ($users['user_hits'] + 1) . "次登录";
                    write_log($mess, 'admin_log');
                    $this->success('身份认证成功,即将登陆',url('admin/Index/index'));
                } else {
                    $error = '用户登陆更新失败';
                }
            }else{
                $error = '密码不正确';
            }

        }else{
            $error = '该用户不存在或者被封';
        }

        $this->error($error);
    }

    //螺丝帽验证码
    static function luosimao_respons($luotest_response)
    {
        $url =plugins_value('lsm_verify','url');
        $data = [
            'response'=>$luotest_response,
            'api_key'=>plugins_value('lsm_verify','api_key')
        ];
        $res=http_curl($url,'post','json',$data);
        return $res;
    }
}