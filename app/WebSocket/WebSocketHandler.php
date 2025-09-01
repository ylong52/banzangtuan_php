<?php

namespace App\WebSocket;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\SurveyFailController;

class WebSocketHandler
{
    protected $server;
    protected $connections = [];
    protected $timerId; // 添加定时器ID属性
    protected $timerStarted = false; // 添加定时器状态标记

    public function __construct(Server $server)
    {
        $this->server = $server;
        // 不在构造函数中启动定时器
    }

    // 删除整个 onHandshake 方法
    /**
     * WebSocket 连接打开
     */
    public function onOpen(Server $server, Request $request): void
    {
        $fd = $request->fd;
        $this->connections[$fd] = [
            'fd' => $fd,
            'user_id' => null,
            'connected_at' => time(),
            'latest_message' => 'aaaa' // 存储前端最新消息，默认值为'aaaa'
        ];
        
        Log::info('WebSocket connection opened', ['fd' => $fd]);
        
        // 如果是第一个连接且定时器还没启动，则启动定时器
        // 在 onOpen 方法中
        if (!$this->timerStarted) {
            $this->startTimer();
            $this->timerStarted = true;
        }
        
        // 发送欢迎消息
        $welcomeMessage = [
            'event' => 'welcome',
            'data' => '欢迎连接到 WebSocket 服务器！'
        ];
        
        $server->push($fd, json_encode($welcomeMessage));
    }

    /**
     * WebSocket 消息处理
     */
    public function onMessage(Server $server, Frame $frame): void
    {
        $fd = $frame->fd;
        $data = $frame->data;
        
        try {
            // 尝试解析 JSON
            $message = json_decode($data, true);
            
            // 如果是 JSON 格式且包含 event 字段，按原逻辑处理
            if ($message && isset($message['event'])) {
                $event = $message['event'];
                $messageData = $message['data'] ?? null;
                
                // 存储前端传来的消息数据
                if (isset($this->connections[$fd])) {
                    $this->connections[$fd]['latest_message'] = $messageData;
                }
                
                Log::info('WebSocket message received', [
                    'fd' => $fd,
                    'event' => $event,
                    'data' => $messageData
                ]);
                
                // 根据事件类型处理消息
                switch ($event) {
                    case 'init':
                        $this->handleInit($server, $fd, $messageData);
                        break;
                        
                    case 'message':
                        $this->handleMessage($server, $fd, $messageData);
                        break;
                        
                    case 'ping':
                        $this->handlePing($server, $fd, $messageData);
                        break;
                        
                    default:
                        $this->sendError($server, $fd, '未知的事件类型: ' . $event);
                        break;
                }
            } else {
                // 如果不是 JSON 格式或没有 event 字段，也存储原始数据
                if (isset($this->connections[$fd])) {
                    $this->connections[$fd]['latest_message'] = $data;
                }
                
                Log::info('WebSocket raw message received', [
                    'fd' => $fd,
                    'data' => $data
                ]);
                
                // 根据连接ID生成不同的响应数据
                $responseData = $this->generateResponseByConnectionId($fd, $data);
                
                // 返回处理后的消息
                $server->push($fd, $responseData);
            }
            
        } catch (\Exception $e) {
            Log::error('WebSocket message processing error', [
                'fd' => $fd,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            // 出错时也根据连接ID返回处理后的数据
            $responseData = $this->generateResponseByConnectionId($fd, $data);
            $server->push($fd, $responseData);
        }
    }

    /**
     * 根据连接ID生成不同的响应数据
     */
    /**
     * 根据连接ID生成不同的响应数据
     */
    protected function generateResponseByConnectionId(int $fd, string $originalData): string
    {
        // 根据连接ID的最后一位数字来决定后缀
        $lastDigit = $fd % 10;        
        // 生成对应的后缀
        $suffix = $lastDigit + 1;        
        // 返回原数据加上'aaaa'、后缀，并包含连接ID信息
        return $originalData . 'aaaa' . $suffix . '_fd###:' . $fd;
    }

    /**
     * 处理初始化消息
     */
    protected function handleInit(Server $server, int $fd, $data): void
    {
        $response = [
            'event' => 'init_response',
            'data' => '初始化成功，连接ID: ' . $fd
        ];
        
        $server->push($fd, json_encode($response));
    }

    /**
     * 处理普通消息
     */
    protected function handleMessage(Server $server, int $fd, $data): void
    {
        // 广播消息给所有连接的客户端
        $broadcastMessage = [
            'event' => 'broadcast',
            'data' => [
                'from' => $fd,
                'message' => $data,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
        // 发送给所有连接的客户端
        foreach ($this->connections as $connectionFd => $connection) {
            if ($server->isEstablished($connectionFd)) {
                $server->push($connectionFd, json_encode($broadcastMessage));
            }
        }
        
        // 确认消息已发送
        $confirmMessage = [
            'event' => 'message_sent',
            'data' => 'message sended! data='.$data
        ];
        
        $server->push($fd, json_encode($confirmMessage));
    }

    /**
     * 处理心跳消息
     */
    protected function handlePing(Server $server, int $fd, $data): void
    {
        $pongMessage = [
            'event' => 'pong',
            'data' => $data
        ];
        
        $server->push($fd, json_encode($pongMessage));
    }

    /**
     * 发送错误消息
     */
    protected function sendError(Server $server, int $fd, string $error): void
    {
        $errorMessage = [
            'event' => 'error',
            'data' => $error
        ];
        
        $server->push($fd, json_encode($errorMessage));
    }

    /**
     * 启动定时器，每5秒发送数据
     */
    protected function startTimer(): void
    {
        $this->timerId = $this->server->tick(5000, function() {
            $this->sendPeriodicData();
        });
        
        Log::info('定时器已启动，每5秒发送一次数据');
        echo "定时器已启动，每5秒发送一次数据\n";
    }

    protected static function sendPeriodicBySurveyFailController($latestMessage)
    {
        if ($latestMessage === null) {
            return null;
        }
        try {
            $surveyFailController = new SurveyFailController();
            $request = new \Illuminate\Http\Request();
            $request->merge(['survey_numbers' => $latestMessage]);
            $response = $surveyFailController->index($request);            
            // 移除调试代码：
            // 正确获取JsonResponse的数据
             $responseData = $response->getData(true); // true参数返回数组而不是对象

           
            // 根据SurveyFailController的返回结构提取datalist
            if (isset($responseData['data']['resDataList']['data'])) {
                return $responseData['data']['resDataList']['data']; // 返回$datalist            
            }
            // 如果结构不匹配，返回空数组
            return [];
            
        } catch (\Exception $e) {
            Log::error('SurveyFailController调用失败: ' . $e->getMessage());
            return [];
        }
     
    }
    /**
     * 发送定时数据
     */
    protected function sendPeriodicData(): void
    {
        if (empty($this->connections)) {
            return;
        }
    
       // 向所有活跃连接发送数据
        foreach ($this->connections as $fd => $connection) {
            if ($this->server->isEstablished($fd)) {
                // 获取该连接的最新消息，如果没有则使用默认值
                $latestMessage = $connection['latest_message'] ?? null;
                
                // 为每个连接生成个性化的消息
                // $periodicMessage = [
                //     'event' => 'periodic_data',
                //     'data' => [
                //         'message' => '浏览器ID:' . $fd . ' ' . $latestMessage . '数据响应',
                //         'browser_id' => $fd,
                //         // 'response_data' => $latestMessage, // 使用前端传来的消息替换'aaaa'
                //         'response_data' => self::sendPeriodicBySurveyFailController($latestMessage), // 使用前端传来的消息替换'aaaa'
                //         'timestamp' => date('Y-m-d H:i:s'),
                //         'server_time' => time(),
                //         'connection_count' => count($this->connections)
                //     ]
                // ];
                
                $periodicMessage = self::sendPeriodicBySurveyFailController($latestMessage) ;
                

                $messageJson = json_encode($periodicMessage);
                $this->server->push($fd, $messageJson);
            } else {
                // 清理无效连接
                unset($this->connections[$fd]);
            }
        }
    }

    /**
     * 停止定时器
     */
    protected function stopTimer(): void
    {
        if ($this->timerId) {
            $this->server->clearTimer($this->timerId);
            $this->timerId = null;
            Log::info('定时器已停止');
        }
    }

    /**
     * WebSocket 连接关闭
     */
    public function onClose(Server $server, int $fd): void
    {
        unset($this->connections[$fd]);
        
        Log::info('WebSocket connection closed', ['fd' => $fd]);
        
        // 如果没有连接了，停止定时器
        if (empty($this->connections) && $this->timerStarted) {
            $this->stopTimer();
            $this->timerStarted = false;
        }
        
        // 通知其他客户端有用户离开
        $leaveMessage = [
            'event' => 'user_leave',
            'data' => '用户 ' . $fd . ' 已离开'
        ];
        
        foreach ($this->connections as $connectionFd => $connection) {
            if ($server->isEstablished($connectionFd)) {
                $server->push($connectionFd, json_encode($leaveMessage));
            }
        }
    }

    
}


