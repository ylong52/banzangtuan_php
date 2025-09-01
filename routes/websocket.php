<?php

use SwooleTW\Http\Websocket\Facades\Websocket;

/*
|--------------------------------------------------------------------------
| WebSocket Routes
|--------------------------------------------------------------------------
|
| Here is where you can register WebSocket events for your application.
|
*/

// WebSocket 连接事件
Websocket::on('connect', function ($websocket, $request) {
    // 处理连接建立
    echo "WebSocket connection established\n";
});

// WebSocket 消息事件
Websocket::on('message', function ($websocket, $frame) {
    // 处理接收到的消息
    $message = $frame->data;
    echo "Received message: {$message}\n";
    
    // 回复消息
    $websocket->push($frame->fd, "Echo: {$message}");
});

// WebSocket 断开连接事件
Websocket::on('disconnect', function ($websocket, $fd) {
    // 处理连接断开
    echo "WebSocket connection {$fd} disconnected\n";
});