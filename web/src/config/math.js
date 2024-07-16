import Vue from "vue";
import {create,all} from 'mathjs';
const math = create(all);
//链式运算
const calc={
	//入口方法
	chain(number){
		this.base=math.bignumber(number);
		return this;
	},
	//加法运算
	add(number){
		this.base=math.format(math.add(this.base,math.bignumber(number)));
		return this;
	},
	//减法运算
	subtract(number){
		this.base=math.format(math.subtract(this.base,math.bignumber(number)));
		return this;
	},
	//乘法运算
	multiply(number){
		this.base=math.format(math.multiply(this.base,math.bignumber(number)));
		return this;
	},
	//除法运算
	divide(number){
		this.base=math.format(math.divide(this.base,math.bignumber(number)));
		return this;
	},
	//绝对运算
	abs(){
		this.base=math.format(math.abs(this.base));
		return this;
	},
	//取余运算
	mod(number){
		this.base=math.format(math.mod(this.base,math.bignumber(number)));
		return this;
	},
	//四舍五入
	round(digit){
		this.base=math.format(math.round(this.base,digit));
		return this;
	},
	//返回结果
	done(){
		return this.base-0;
	}
}
Vue.prototype.$calc = calc;