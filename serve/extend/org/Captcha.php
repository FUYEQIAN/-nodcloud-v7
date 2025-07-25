<?php
/*
BY:NODCLOUD.COM
*/
namespace org;
class Captcha {
	public static $codeSet = '346789ABCDEFGHJKLMNPQRTUVWXY';
	public static $fontSize = 25;     // 验证码字体大小(px)
	public static $useCurve = true;   // 是否画混淆曲线
	public static $useNoise = true;   // 是否添加杂点
	public static $imageH = 0;        // 验证码图片宽
	public static $imageL = 0;        // 验证码图片长
	public static $length = 4;        // 验证码位数
	public static $bg = [243, 251, 254];  // 背景
	protected static $_image = null;     // 验证码图片实例
	protected static $_color = null;     // 验证码字体颜色
	
	/**
	 * 输出验证码|BASE64
	 */
	public static function entry() {
		// 图片宽(px)
		self::$imageL || self::$imageL = self::$length * self::$fontSize * 1.5 + self::$fontSize*1.5; 
		// 图片高(px)
		self::$imageH || self::$imageH = self::$fontSize * 2;
		// 建立一幅 self::$imageL x self::$imageH 的图像
		self::$_image = imagecreate(self::$imageL, self::$imageH); 
		// 设置背景      
		imagecolorallocate(self::$_image, self::$bg[0], self::$bg[1], self::$bg[2]); 
		// 验证码字体随机颜色
		self::$_color = imagecolorallocate(self::$_image, mt_rand(1,120), mt_rand(1,120), mt_rand(1,120));
		// 验证码使用随机字体 
		$ttf = dirname(__FILE__).'/ttfs/'.mt_rand(1, 6).'.ttf';
		// 绘杂点
		if (self::$useNoise) {
			self::_writeNoise();
		} 
		// 绘干扰线
		if (self::$useCurve) {
			self::_writeCurve();
		}
		// 绘验证码
		$code = []; // 验证码
		$codeNX = 0; // 验证码第N个字符的左边距
		for ($i = 0; $i<self::$length; $i++) {
			$code[$i] = self::$codeSet[mt_rand(0, 27)];
			$codeNX += mt_rand(self::$fontSize*1.2, self::$fontSize*1.6);
			// 写一个验证码字符
			imagettftext(self::$_image, self::$fontSize, mt_rand(-40, 70), $codeNX, self::$fontSize*1.5, self::$_color, $ttf, $code[$i]);
		}
		//解析数据
		ob_start ();
        imagepng(self::$_image);
        $imageData=ob_get_contents();
        imagedestroy(self::$_image);
        ob_end_clean();
        // 返回数据
        return [
            'code'=>join("",$code),
            'data'=>"data:image/png;base64,".base64_encode($imageData)
        ];
	}
	
	/** 
	 * 画一条由两条连在一起构成的随机正弦函数曲线作干扰线(你可以改成更帅的曲线函数) 
     *      
     *      高中的数学公式咋都忘了涅，写出来
	 *		正弦型函数解析式：y=Asin(ωx+φ)+b
	 *      各常数值对函数图像的影响：
	 *        A：决定峰值（即纵向拉伸压缩的倍数）
	 *        b：表示波形在Y轴的位置关系或纵向移动距离（上加下减）
	 *        φ：决定波形与X轴位置关系或横向移动距离（左加右减）
	 *        ω：决定周期（最小正周期T=2π/∣ω∣）
	 *
	 */
    protected static function _writeCurve() {
		$A = mt_rand(1, self::$imageH/2);                  // 振幅
		$b = mt_rand(-self::$imageH/4, self::$imageH/4);   // Y轴方向偏移量
		$f = mt_rand(-self::$imageH/4, self::$imageH/4);   // X轴方向偏移量
		$T = mt_rand(self::$imageH*1.5, self::$imageL*2);  // 周期
		$w = (2* M_PI)/$T;
		$px1 = 0;  // 曲线横坐标起始位置
		$px2 = mt_rand(self::$imageL/2, self::$imageL * 0.667);  // 曲线横坐标结束位置 	    	
		for ($px=$px1; $px<=$px2; $px=$px+ 0.9) {
			if ($w!=0) {
				$py = $A * sin($w*$px + $f)+ $b + self::$imageH/2;  // y = Asin(ωx+φ) + b
				$i = (int) ((self::$fontSize - 6)/4);
				while ($i > 0) {	
				    imagesetpixel(self::$_image, $px + $i, $py + $i, self::$_color);  // 这里画像素点比imagettftext和imagestring性能要好很多				    
				    $i--;
				}
			}
		}
		$A = mt_rand(1, self::$imageH/2);                  // 振幅		
		$f = mt_rand(-self::$imageH/4, self::$imageH/4);   // X轴方向偏移量
		$T = mt_rand(self::$imageH*1.5, self::$imageL*2);  // 周期
		$w = (2* M_PI)/$T;		
		$b = $py - $A * sin($w*$px + $f) - self::$imageH/2;
		$px1 = $px2;
		$px2 = self::$imageL;
		for ($px=$px1; $px<=$px2; $px=$px+ 0.9) {
			if ($w!=0) {
				$py = $A * sin($w*$px + $f)+ $b + self::$imageH/2;  // y = Asin(ωx+φ) + b
				$i = (int) ((self::$fontSize - 8)/4);
				while ($i > 0) {			
				    imagesetpixel(self::$_image, $px + $i, $py + $i, self::$_color);  // 这里(while)循环画像素点比imagettftext和imagestring用字体大小一次画出（不用这while循环）性能要好很多	
				    $i--;
				}
			}
		}
	}
	
	/**
	 * 画杂点
	 * 往图片上写不同颜色的字母或数字
	 */
	protected static function _writeNoise() {
		for($i = 0; $i < 10; $i++){
			//杂点颜色
		    $noiseColor = imagecolorallocate(
                self::$_image, 
                mt_rand(150,225), 
                mt_rand(150,225), 
                mt_rand(150,225)
            );
			for($j = 0; $j < 5; $j++) {
				// 绘杂点
			    imagestring(
			        self::$_image,
			        5, 
			        mt_rand(-10, self::$imageL), 
			        mt_rand(-10, self::$imageH), 
			        self::$codeSet[mt_rand(0, 27)], // 杂点文本为随机的字母或数字
			        $noiseColor
			    );
			}
		}
	}
}