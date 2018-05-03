<?php
namespace app\home\controller;
use GatewayClient\Gateway;
use think\cache\driver\Memcache;
use think\Db;

/**
 * 房间
 */
class Room extends Base
{
    static $cid;
    static $mem;
    function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        if (input('?cid')) {
            self::$cid = open_secret(input('cid'));
        }
        vendor('gateway.gatewayclient.Gateway');
//        Gateway::$registerAddress = 'card.dcwen.top:1238';
    }

    //房间列表
    function room_page()
    {
        $map = [
            'room_owner'=>self::$cid,
            'del_time'=>0
        ];

        $rooms=Db::name('room')
//            ->order('room_date desc')
            ->where($map)
            ->field('room_id,round_times,room_code')
            ->select();
        self::$mem = new Memcache();

        if (self::$mem->has('last_join_room'.self::$cid)) {
            if (empty($rooms)) {
                $rooms = [];
            }
            $last_room = self::$mem->get('last_join_room'.self::$cid);//获取上个房间信息
            array_push($rooms, $last_room);
        }
        rsort($rooms);
        foreach ($rooms as $k=>$v){
            $inner = "inner".$v['room_id'];
            $onlineNums = Gateway::getClientCountByGroup($inner);//获取当前房内人数
            $rooms[$k]['onlineNums'] = $onlineNums ? $onlineNums : 0;
        }
        echo edd_success($rooms);
    }

    //房间添加逻辑
    function add_room_think()
    {
        self::$mem = new Memcache();
        if (self::$mem->has('in_one_home' . self::$cid)) {
            $in_one_code = self::$mem->get('in_one_home' . self::$cid);
            echo edd_error('您正在'.$in_one_code.'号房间进行游戏,无法创建房间');//防止同时进入多个房间
            die;
        }

        $rooms=room_charge(input('round_id'));//获取该局数房间详情
        if (empty($rooms)) {
            echo edd_error('该房间局数不存在');
            die;
        }
        if (player_diamond(self::$cid) <= $rooms['diamond']) {
            echo edd_error('您的钻石余额尚且不足');
            die;
        }

        $addMap = [
            'room_owner'=>self::$cid,
            'round_times'=>$rooms['round_times'],
            'room_charge'=>$rooms['diamond'],
            'double'=>input('is_double'),
            'room_type' => input('room_type'),
            'room_date' => time(),
            'showpoker'=>input('showpoker')
        ];

        if ($room_id=Db::name('room')->insertGetId($addMap)) {
            $saveMap = [
                'room_id' => $room_id,
                'room_code' => str_pad($room_id,6,'0',STR_PAD_LEFT),//房间号
            ];

            if (Db::name('room')->update($saveMap)) {
                save_player_diamond(self::$cid, $rooms['diamond'], 'dec');

                self::$mem->set($room_id.'room_id', $room_id);//房间号

                $addMap = array_merge($addMap, $saveMap);

                self::$mem->set($room_id.'room_info', $addMap);//房间基本信息
                self::$mem->tag($room_id . 'tag', [$room_id . 'room_id', $room_id . 'room_info']);//房间打包

                $addMap['start_btn'] = false;
                if ($addMap['room_owner'] == self::$cid) {
                    $addMap['start_btn'] = true;
                }

                echo edd_success($addMap);
            }else{
                echo edd_error('房间创建失败');
            }
        }
    }

    //删除房间弹窗显示
    function alert_agree()
    {
        $room_id = input('room_id');
        $room_one_round = 'room_one_round' . $room_id;


        //没有开始，房主可以直接删除该房间
        $map = [
            'room_owner'=>self::$cid,
        ];
        $is_room_arr = Db::name('room')
            ->where($map)
            ->column('room_id');
        self::$mem = new Memcache();
        $room_all_people_key = 'room_all_people' . $room_id;//该房间所有玩家id
        $room_all_people_is = self::$mem->has($room_all_people_key);
        $room_all_people = self::$mem->get($room_all_people_key);

        if (self::$mem->has($room_one_round)) {
            if ($room_all_people_is && in_array(self::$cid, $room_all_people)) {
                //如果已经打完第一轮
                $playerInfo = get_player_info(self::$cid);
                $playername = $playerInfo['name'];
                $room_agree = 'room_agree' . $room_id;
                self::$mem->set($room_agree, 1);
                self::$mem->tag($room_id . 'tag',[$room_agree]);

                $inner = 'inner' . $room_id;
                $map = [
                    'player_id'=>self::$cid,
                    'msg' => $playername . '发起了解散房间请求,是否同意解散?',
                ];
                //删除房间人id存入
                $del_room_player = 'del_room_player' . $room_id;
                self::$mem->set($del_room_player, self::$cid);
                self::$mem->tag($room_id . 'tag', [$del_room_player]);

                Gateway::sendToGroup($inner, send_client($map, 'show_agree_alert'));
                echo edd_success('ok');
            }else{
                echo edd_error('好好看牌，别瞎操作^_^');
            }
        }else{
            if (in_array($room_id, $is_room_arr)) {
                echo edd_success('lt_one');
            }else{
                echo edd_error('您无法解散该房间');
            }
            //如果没有打完第一轮
        }
    }
    //解散房间
    function del_room()
    {
        $room_id = input('room_id');//房间id
        self::$mem = new Memcache();
        $room_one_round = 'room_one_round' . $room_id;
        $watch_dog = 'watch_dog' . $room_id;

        //没有开始，房主可以直接删除该房间
        $map = [
            'room_owner'=>self::$cid,
        ];

        $is_room_arr = Db::name('room')
            ->where($map)
            ->column('room_id');

            if (self::$mem->has($room_one_round)) {
                $inner = 'inner' . $room_id;
                //该房间是否牌局开始，四个人必须都同意，如果有人掉线，直接托管一局，然后删除，
                //删除该局之前保存数据
                $room_agree = 'room_agree' . $room_id;
                $answer = input('answer_type');
                if ($answer=='yes') {
                    //同意删除房间
                        self::$mem->inc($room_agree, 1);
                        $map = [
                            'player_id'=>self::$cid,
                            'agree_type' => 'yes',
                        ];
                        Gateway::sendToGroup($inner, send_client($map, 'say_yes_no'));

                    if (self::$mem->get($room_agree) == 4) {
                        if (!empty(del_room($room_id))) {
                            Gateway::sendToGroup('inner' . $room_id,send_client(['该房间已经被房主解散'],'agree_close_alert'));
                            Gateway::sendToGroup($watch_dog,send_client(['该房间已经被房主解散'],'agree_close_alert'));

                            $map = [
                                'room_id' => $room_id,
                            ];
                            Gateway::sendToGroup('outter' . $room_id,send_client($map,'rm_room'));//给房间外的房间创始人发送消息
                            Gateway::sendToUid('out_client' . self::$cid,send_client($map,'rm_room'));
                            self::$mem->clear($room_id . 'tag');
                        }else{
                            echo edd_error('房间解散失败');
                        }

                    }
                }elseif($answer=='no'){
                    //拒绝解散房间
                    $map = [
                        'player_id'=>self::$cid,
                        'agree_type' => 'no',
                    ];

                    //删除房间人id存入
                    $del_room_player = 'del_room_player' . $room_id;
                    $del_room_player_id=self::$mem->get($del_room_player);

                    Gateway::sendToUid( 'in_client' .$del_room_player_id, send_client(['删除房间解散定时器'], 'del_room_time'));
                    Gateway::sendToGroup($inner, send_client($map, 'say_yes_no'));
                }
                echo edd_success('ok');
            }else{
                if (in_array($room_id, $is_room_arr)) {
                    if (Db::name('room')->delete($room_id)) {
                        self::del_room_think($room_id);
                    }
                }else{
                    echo edd_error('您无法解散该房间');
                }
            }
        }
    //删除房间逻辑
    static function del_room_think($room_id)
    {
        self::$mem = new Memcache();
        //给尚未进入游戏，但是已经坐下的玩家提醒
        $client_id = Gateway::getClientIdByUid('in_client' . self::$cid);
        Gateway::sendToGroup('inner' . $room_id,send_client(['该房间已经被房主解散'],'warning'),$client_id);

        //给当前房间的看客提醒
        $watch_client_id = Gateway::getClientIdByUid('watch_room' . self::$cid);
        $watch_dog = 'watch_dog' . $room_id;
        Gateway::sendToGroup($watch_dog, send_client(['该房间已经被房主解散'],'warning'),$watch_client_id);

        $map = [
            'room_id' => $room_id,
        ];
        Gateway::sendToGroup('outter' . $room_id,send_client($map,'rm_room'),$client_id);//给房间外的房间创始人发送消息
        Gateway::sendToUid('out_client' . self::$cid,send_client($map,'rm_room'));//

        $room = get_home_info($room_id);
        save_player_diamond(self::$cid, $room['room_charge'], 'inc');

        self::$mem->clear($room_id . 'tag');
        echo edd_success('成功解散房间');
    }

    //加入房间
    function join_room()
    {
        self::$mem = new Memcache();
        $room_code = input('room_code');
        $map = [
            'room_code' => $room_code,
            'del_time'=>0
        ];
        $room_id = Db::name('room')
            ->where($map)
            ->value('room_id');
        if (!empty($room_id)) {
                if (self::$mem->has('in_one_home' . self::$cid)) {
                    $in_one_code = self::$mem->get('in_one_home' . self::$cid);
                    if ($room_code != $in_one_code) {
                        echo edd_error('您正在'.$in_one_code.'号房间进行游戏');//防止同时进入多个房间
                        die;
                    }
                }
            $room = self::$mem->get($room_id . 'room_info');
            if (empty($room)) {
                echo edd_error('该房间不存在');
                die;
            }
            $room['start_btn'] = false;
            if ($room['room_owner'] == self::$cid) {
                $room['start_btn'] = true;
            }
            echo edd_success($room);
        }else{
            echo edd_error('该房间不存在');
        }
    }

    //获取某个房间
    function get_room()
    {
        self::$mem = new Memcache();
        if (self::$mem->has(input('room_id') . 'room_info')) {
            echo edd_success(self::$mem->get(input('room_id') . 'room_info'));
        }else{
             echo edd_error('没有该房间');
        }
    }
}