/**
 * 中国风中奖弹窗组件
 * 使用方法：
 *   LotteryModal.show({
 *     prizeName: '一等奖',
 *     prizeMessage: '您获得了：iPhone 15 Pro Max'
 *   });
 */

(function() {
    'use strict';

    const LotteryModal = {
        // 显示中奖弹窗
        show: function(options) {
            options = options || {};
            
            const prizeName = options.prizeName || '恭喜中奖';
            const prizeMessage = options.prizeMessage || '';
            const onClose = options.onClose || null;
            const isNoPrize = options.isNoPrize || false;

            // 创建遮罩层
            const overlay = document.createElement('div');
            overlay.className = 'lottery-modal-overlay';
            overlay.id = 'lotteryModalOverlay';

            // 创建弹窗容器
            const container = document.createElement('div');
            container.className = 'lottery-modal-container' + (isNoPrize ? ' no-prize' : '');

            // 创建关闭按钮
            const closeBtn = document.createElement('span');
            closeBtn.className = 'lottery-modal-close';
            closeBtn.innerHTML = '×';
            closeBtn.onclick = function() {
                LotteryModal.close(onClose);
            };

            // 创建标题
            const title = document.createElement('h2');
            title.className = 'lottery-modal-title';
            title.textContent = isNoPrize ? '很遗憾' : '恭喜中奖';

            // 创建奖品容器
            const prizeDiv = document.createElement('div');
            prizeDiv.className = 'lottery-modal-prize';
            
            const prizeNameDiv = document.createElement('div');
            prizeNameDiv.className = 'lottery-modal-prize-name';
            prizeNameDiv.textContent = prizeName;
            prizeDiv.appendChild(prizeNameDiv);

            // 创建消息
            const message = document.createElement('p');
            message.className = 'lottery-modal-message';
            message.textContent = prizeMessage || (isNoPrize ? '未中奖，欢迎下次再来' : '');

            // 创建确认按钮
            const button = document.createElement('button');
            button.className = 'lottery-modal-button';
            button.textContent = '确定';
            button.onclick = function() {
                LotteryModal.close(onClose);
            };

            // 组装弹窗
            container.appendChild(closeBtn);
            container.appendChild(title);
            if (!isNoPrize) {
                container.appendChild(prizeDiv);
            }
            container.appendChild(message);
            container.appendChild(button);

            // 添加装饰性闪光点（仅中奖时显示）
            if (!isNoPrize) {
                const sparkles = [
                    { top: '20%', left: '15%', delay: '0s' },
                    { top: '30%', right: '20%', delay: '0.5s' },
                    { top: 'auto', bottom: '25%', left: '25%', delay: '1s' },
                    { top: 'auto', bottom: '35%', right: '15%', delay: '1.5s' }
                ];

                sparkles.forEach(function(sparkle) {
                    const sparkleDiv = document.createElement('div');
                    sparkleDiv.className = 'lottery-modal-sparkle';
                    let style = 'animation-delay: ' + sparkle.delay + ';';
                    if (sparkle.top) style += ' top: ' + sparkle.top + ';';
                    if (sparkle.bottom) style += ' bottom: ' + sparkle.bottom + ';';
                    if (sparkle.left) style += ' left: ' + sparkle.left + ';';
                    if (sparkle.right) style += ' right: ' + sparkle.right + ';';
                    sparkleDiv.setAttribute('style', style);
                    container.appendChild(sparkleDiv);
                });
            }

            overlay.appendChild(container);
            document.body.appendChild(overlay);

            // 点击遮罩层关闭
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    LotteryModal.close(onClose);
                }
            });

            // ESC键关闭
            const escHandler = function(e) {
                if (e.key === 'Escape') {
                    LotteryModal.close(onClose);
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);

            // 防止背景滚动
            document.body.style.overflow = 'hidden';
        },

        // 关闭弹窗
        close: function(callback) {
            const overlay = document.getElementById('lotteryModalOverlay');
            if (overlay) {
                overlay.style.animation = 'fadeOut 0.3s ease-in-out';
                setTimeout(function() {
                    overlay.remove();
                    document.body.style.overflow = '';
                    if (callback && typeof callback === 'function') {
                        callback();
                    }
                }, 300);
            }
        },

        // 显示未中奖弹窗
        showNoPrize: function(options) {
            options = options || {};
            options.isNoPrize = true;
            options.prizeName = options.prizeName || '很遗憾';
            options.prizeMessage = options.prizeMessage || '未中奖，欢迎下次再来';
            this.show(options);
        }
    };

    // 添加淡出动画
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // 导出到全局
    if (typeof window !== 'undefined') {
        window.LotteryModal = LotteryModal;
    }

    // 如果使用模块系统
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = LotteryModal;
    }
})();

