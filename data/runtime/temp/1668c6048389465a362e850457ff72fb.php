<?php if (!defined('THINK_PATH')) exit(); /*a:3:{s:54:"/var/www/card/app/admin/view/admin/edit_self_page.html";i:1502609781;s:48:"/var/www/card/app/admin/view/public/head_in.html";i:1502609787;s:46:"/var/www/card/app/admin/view/public/alert.html";i:1502609787;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
    <link rel="icon" href="__UPLOAD__/admin/common/logo.png" type="image/x-icon" />
<link rel="stylesheet" type="text/css" id="theme" href="__ADMIN__/css/theme-default-in.css"/>
<!-- EOF CSS INCLUDE -->

<!-- START PLUGINS -->
<script type="text/javascript" src="__ADMIN__/js/plugins/jquery/jquery.min.js"></script>
<script type="text/javascript" src="__ADMIN__/js/plugins/jquery/jquery-ui.min.js"></script>
<script type="text/javascript" src="__ADMIN__/js/plugins/bootstrap/bootstrap.min.js"></script>
<!-- END PLUGINS -->

<!-- THIS PAGE PLUGINS -->
<script type='text/javascript' src='__ADMIN__/js/plugins/icheck/icheck.min.js'></script>
<script type="text/javascript" src="__ADMIN__/js/plugins/mcustomscrollbar/jquery.mCustomScrollbar.min.js"></script>

<script type="text/javascript" src="__ADMIN__/js/plugins/bootstrap/bootstrap-datepicker.js"></script>
<script type="text/javascript" src="__ADMIN__/js/plugins/bootstrap/bootstrap-file-input.js"></script>
<script type="text/javascript" src="__ADMIN__/js/plugins/bootstrap/bootstrap-select.js"></script>
<script type="text/javascript" src="__ADMIN__/js/plugins/tagsinput/jquery.tagsinput.min.js"></script>
<script type="text/javascript" src="__ADMIN__/js/plugins/datatables/jquery.dataTables.min.js"></script>

<!-- END THIS PAGE PLUGINS -->
<script type="text/javascript" src="__ADMIN__/js/plugins.js"></script>
<script type="text/javascript" src="__ADMIN__/js/actions.js"></script>


<link rel="stylesheet" href="__ADMIN__/plugins/message_alert/css/m_css.css">
<script src="__ADMIN__/plugins/message_alert/js/m_js.js"></script>
<script src="__ADMIN__/plugins/layer-v3.0.1/layer/layer.js"></script>
<script src="__ADMIN__/plugins/ajax-form/ajax-form.js"></script>

<!--MY-->
<script src="__PUBLIC__/admin/controller/upload.js"></script>
<script src="__PUBLIC__/admin/controller/controller.js"></script>
<!--前台框架自带的弹出层-->
<div class="message-box animated fadeIn message-box-info" data-sound="alert" id="mb-signout">
    <div class="mb-container">
        <div class="mb-middle">
            <div class="mb-title"><span class="fa fa-sign-out"></span><strong>退出</strong> ?</div>
            <div class="mb-content">
                <p>你确定要退出?</p>
                <p>如果你想继续操作后台请按‘否’. 按‘是’则回到登录页面.</p>
            </div>
            <div class="mb-footer">
                <div class="pull-right">
                    <a href="<?php echo url('admin/Index/login_out'); ?>" class="btn btn-success btn-lg">是</a>
                    <button class="btn btn-default btn-lg mb-control-close">否</button>
                </div>
            </div>
        </div>
    </div>
</div>
<!--自定义弹出提示-->
<div class="m_tip">
    <div>
        <img class="m_icon m_normal" src="__ADMIN__/plugins/message_alert/img/tip.svg">
        <img class="m_icon m_error" src="__ADMIN__/plugins/message_alert/img/error.svg">
        <img class="m_icon m_success" src="__ADMIN__/plugins/message_alert/img/success.svg">
        <img class="m_icon m_warning" src="__ADMIN__/plugins/message_alert/img/warning.svg">
        <img class="m_icon m_loading" src="__ADMIN__/plugins/message_alert/img/loading.svg">
        <span>提示内容</span>
    </div>
    <img class="m_icon m_close" src="__ADMIN__/plugins/message_alert/img/close.svg" onclick=close_tip(this)>
</div>

</head>
<body>
<div class="panel panel-default">
    <form id="edit_form" action="<?php echo url('admin/Admin/edit_think'); ?>" method="post" class="form-horizontal">
        <div class="panel-body">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <div class="row">
                <div class="col-xs-10">
                    <div class="form-group">
                        <label class="col-xs-3 control-label">头像</label>
                        <div class="col-xs-6">
                            <div class="gallery">
                                <a class="gallery-item"  href="javascript:void('')" title="Space picture 2" data-gallery>
                                    <div class="image" >
                                        <input value="<?php echo $avatar; ?>" hidden name="avatar" type="text" id="inp">
                                        <img src="<?php echo is_img($avatar); ?>" alt="Space picture 2"/>
                                        <ul class="gallery-item-controls">
                                            <li onclick="upload_single('inp','avatar')"><i class="fa fa-cloud-upload"></i></li>
                                            <li onclick="del_pic('inp')"><i class="fa fa-times"></i></li>
                                        </ul>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label">登录名</label>
                        <div class="col-xs-9">
                            <input name="user_login" value="<?php echo $user_login; ?>" type="text" class="form-control"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label">密码</label>
                        <div class="col-xs-9">
                            <input name="user_pass" type="password" class="form-control"/>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-xs-3 control-label">确认密码</label>
                        <div class="col-xs-9">
                            <input name="confirm_pass"  type="password" class="form-control"/>
                        </div>
                    </div>
                    <input type="text" hidden name="user_status" value="<?php echo $user_status; ?>">
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button class="btn btn-info pull-right">保存修改<span class="fa fa-floppy-o fa-right"></span></button>
        </div>
    </form>
</div>
</body>
</html>