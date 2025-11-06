<?php
// 测试正则表达式
$txt = "9折券https://u.jd.com/SDRfLb0(晚八点生效)";
$pattern1 = '/https?:\/\/[a-zA-Z0-9\.\/\:\-\_\?\=\&\%]+/u';

echo "原始文本: " . $txt . "\n";
echo "正则表达式: " . $pattern1 . "\n\n";

if (preg_match($pattern1, $txt, $matches)) {
    echo "匹配成功!\n";
    echo "匹配结果: " . $matches[0] . "\n";
} else {
    echo "匹配失败!\n";
}

// 测试多个链接
$txt2 = "多个链接 https://u.jd.com/SDRfLb0(晚八点生效) 和 https://www.example.com/path?param=value(中文)";
preg_match_all($pattern1, $txt2, $matches2);
echo "\n多个链接测试:\n";
echo "原始文本: " . $txt2 . "\n";
echo "匹配结果:\n";
foreach ($matches2[0] as $i => $match) {
    echo "  " . ($i + 1) . ": " . $match . "\n";
}
?>
