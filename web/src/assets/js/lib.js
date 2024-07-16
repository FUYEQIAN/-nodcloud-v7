import Vue from 'vue';
import qs from 'qs';
import store from '@/config/store';
const lib = {
    //更新VUEX数据
    updateStore(that) {
        that.$axios.post('service/store').then((result) => {
            if (result.state == 'success') {
                store.commit('upState', {
                    key: 'frame',
                    val: result.info.frame
                });
                store.commit('upState', {
                    key: 'menu',
                    val: result.info.menu
                });
                store.commit('upState', {
                    key: 'fields',
                    val: result.info.fields
                });
                store.commit('upState', {
                    key: 'root',
                    val: result.info.root
                });
                store.commit('upState', {
                    key: 'category',
                    val: result.info.category
                });
                store.commit('upState', {
                    key: 'warehouse',
                    val: result.info.warehouse
                });
                store.commit('upState', {
                    key: 'account',
                    val: result.info.account
                });
                store.commit('upState', {
                    key: 'iet',
                    val: result.info.iet
                });
                store.commit('upState', {
                    key: 'often',
                    val: result.info.often
                });
                store.commit('upState', {
                    key: 'sys',
                    val: result.info.sys
                });
            } else if (result.state == 'error') {
                that.$message({
                    type: 'warning',
                    message: result.info
                });
            } else {
                that.$message({
                    type: 'error',
                    message: '[ ERROR ] 服务器响应超时!'
                });
            }
        });
    },
    //用户权限判断
    getUserRoot(root, module, type) {
        if (root == 'all') {
            return true;
        } else {
            if (root[module][type] == true) {
                return true;
            } else {
                return false;
            }
        }
    },
    //单独表单验证
    aloneValidate(form, rules) {
        return Promise.all(
            rules.map((rule) => {
                return new Promise((resolve, reject) => {
                    form.validateField(rule, (message) => {
                        message == '' ? resolve(message) : reject(message);
                    });
                });
            })
        );
    },
    //正则处理
    validate(key, val) {
        let regular = {
            empty: /^\s*$/g, //空
            tel: /^1\d{10}$/, //手机号
            phone: /^(\(\d{3,4}\)|\d{3,4}-|\s)?\d{7,14}$/, //座机号
            tax: /^[A-Z0-9]{15}$|^[A-Z0-9]{17}$|^[A-Z0-9]{18}$|^[A-Z0-9]{20}$/, //税号
            number: /^\d+$/, //数字组合
            integer: /^[1-9]+\d*$/, //正整数不含0
            plus: /^\d+(\.\d{1,4})?$/, //正数4位小数
            invoice: /^\d+(\.\d{1,2})?$/, //正数2位小数
            email: /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/, //邮箱
            time: /^(19|20)\d{2}-(0?\d|1[012])-(0?\d|[12]\d|3[01])$/, //时间正则
            numerical: /^(\-)?\d+(\.\d{1,4})?$/, //正负数值4位小数
            scale: /^(1|0(\.\d{1,2})?)$/, //0-1区间|两位小数,
            percentage: /^(\d{1,2}(\.\d{1,2})?|100)$/, //0-100区间|两位小数,
            serial: /^[0-9a-zA-Z\-\_\|]*$/, //序列号正则
            variable: /^[a-zA-Z][a-zA-Z0-9_]*$/, //变量命名规则
            numeral: /^[0-9]/, //数字开头,
            capital: /[A-Z]/ //大写字母
        };
        return regular[key].test(val);
    },
    //动态正则处理
    synValidate(key, val) {
        let digit = store.state.sys.fun.digit;
        let regular = {
            nums: '^\\d+(\\.\\d{0,' + digit.nums + '})?$', //数量正则
            money: '^\\d+(\\.\\d{0,' + digit.money + '})?$', //金额正则
            numerical: '^(\\-)?\\d+(\\.\\d{0,' + digit.money + '})?$' //金额正则
        };
        return new RegExp(regular[key]).test(val);
    },
    //多单位|关系获取
    unitRelation(unit, units, that) {
        let relation = { multiple: 1, discount: 0 };
        let data = [{ name: units[0].source, nums: 1, discount: 0 }].concat(units);
        for (let item of data) {
            relation.multiple = that.$calc.chain(relation.multiple).multiply(item.nums).done();
            if (item.name == unit) {
                relation.discount = that.$calc.chain(100).subtract(item.discount).divide(100).done();
                break;
            }
        }
        return relation;
    },
    //多单位分析|单位转换
    unitSwitch(nums, units, that) {
        let record = [];
        for (let item of units) {
            if (nums < item.nums) {
                record.push({ name: item.source, nums: nums });
                nums = 0;
            } else {
                let mod = that.$calc.chain(nums).mod(item.nums).done();
                record.push({ name: item.source, nums: mod });
                nums = that.$calc.chain(nums).subtract(mod).divide(item.nums).done();
            }
        }
        let end = units[units.length - 1];
        record.push({ name: end.name, nums: nums });
        let text = '';
        for (let item of record.reverse()) {
            if (item.nums != 0) {
                text += item.nums + item.name;
            }
        }
        text == '' && (text = 0);
        return text;
    },
    //等级折扣
    gradeDiscount(grade, strategy) {
        let find = strategy.find((item) => item.grade == grade);
        return find ? find.discount : 0;
    },
    //换行处理
    nl2br(text) {
        return typeof text == 'string' ? text.replace(/[(\r\n)\r\n]+/g, '<br/>') : false;
    },
    //生成唯一标识|首字母不为数字
    getUniqid() {
        let random1 = Number(Math.random().toString().substr(3, 9));
        let random2 = Number(
            Date.now()
                .toString()
                .split('')
                .sort(() => Math.random() - 0.5)
                .join('')
        );
        let uniqid = (random1 + random2).toString(36);
        return this.validate('numeral', uniqid) ? this.randomLetters(1) + uniqid.substr(1) : uniqid;
    },
    //随机生成数字
    randomNumber(length) {
        let number = Math.random().toString();
        return number.substr(2, length);
    },
    //随机生成字母
    randomLetters(length) {
        let arr = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'];
        let idvalue = '';
        for (let i = 0; i < length; i++) {
            idvalue += arr[Math.floor(Math.random() * 26)];
        }
        return idvalue;
    },
    //字符唯一编码|unique编码累加转进制
    charUnique(str, hex = 32) {
        let unique = '';
        let items = str.split('');
        for (let item of items) {
            unique += item.charCodeAt().toString(hex);
        }
        return unique;
    },
    //字节转文件大小
    bytesToSize(bytes) {
        if (bytes === 0) return '0 B';
        let k = 1024,
            sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
            i = Math.floor(Math.log(bytes) / Math.log(k));
        return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + sizes[i];
    },
    //字符串首字母大写
    firstToUpperCase(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    },
    //判断变量类型是否相等
    VariableEqual(parmA, parmB) {
        return Object.prototype.toString.call(parmA) == Object.prototype.toString.call(parmB);
    },
    //判断元素是否可视
    isDomVisual(el, dom, type = 'all') {
        let elData = el.getBoundingClientRect(),
            domData = dom.getBoundingClientRect(),
            top = elData.top - domData.top,
            bottom = elData.bottom - domData.top,
            left = elData.left - domData.left,
            right = elData.right - domData.left,
            xVisual = left >= 0 && left < domData.width && right >= 0 && right <= domData.width,
            yVisual = top >= 0 && top < domData.height && bottom >= 0 && bottom <= domData.height;
        if (domData.width >= elData.width && domData.height >= elData.height) {
            if (type == 'x') {
                return xVisual;
            } else if (type == 'y') {
                return yVisual;
            } else {
                return xVisual && yVisual;
            }
        } else {
            return null;
        }
    },
    //数组去重
    distinct(arr) {
        return Array.from(new Set(arr));
    },
    //数组交集
    intersect(arr1, arr2) {
        return new Set([...new Set(arr1)].filter((x) => new Set(arr2).has(x)));
    },
    //数组树结构扁平化
    flatten(data, sub = 'sub') {
        return data.reduce((arr, obj) => arr.concat([obj], this.flatten(obj[sub])), []);
    },
    //返回数组指定列集合
    arrayColumn(arr, field) {
        let data = [];
        arr.forEach((item) => {
            data.push(item[field]);
        });
        return data;
    },
    //数组指定位数拆分
    chunk(arr, size) {
        return Array.from({ length: Math.ceil(arr.length / size) }, (v, i) => arr.slice(i * size, i * size + size));
    },
    //判断是否JSON
    isJSON(str) {
        if (typeof str == 'string') {
            try {
                let obj = JSON.parse(str);
                if (typeof obj == 'object' && obj) {
                    return true;
                } else {
                    return false;
                }
            } catch (e) {
                return false;
            }
        } else {
            return false;
        }
    },
    //对象转URL参数
    //是否附带凭证
    objToParm(obj, token = false) {
        if (token) obj = Object.assign({}, obj, { token: store.state.token });
        return qs.stringify(obj);
    },
    //寻找对象多层键名
    //source:"键名1|键名2"
    //如查找过程键名不存在返回空
    objectSeek(array, source) {
        let recode = array;
        for (let key of source.split('|')) {
            if (this.isPlainObject(recode) && recode.hasOwnProperty(key)) {
                recode = recode[key];
            } else {
                recode = '';
                break;
            }
        }
        return recode;
    },
    //判断空对象
    isObjEmpty(obj) {
        return Object.keys(obj).length === 0;
    },
    //对象判断
    isPlainObject(obj) {
        var proto, Ctor;
        if (!obj || toString.call(obj) !== '[object Object]') {
            return false;
        }
        proto = Object.getPrototypeOf(obj);
        if (!proto) {
            return true;
        }
        Ctor = {}.hasOwnProperty.call(proto, 'constructor') && proto.constructor;
        return typeof Ctor === 'function' && {}.hasOwnProperty.toString.call(Ctor) === {}.hasOwnProperty.toString.call(Object);
    },
    //拷贝数据|JQUERY移植
    extend() {
        let options,
            name,
            src,
            copy,
            copyIsArray,
            clone,
            target = arguments[0] || {},
            i = 1,
            length = arguments.length,
            deep = false;
        if (typeof target === 'boolean') {
            deep = target;
            target = arguments[i] || {};
            i++;
        }
        if (typeof target !== 'object' && !isFunction(target)) {
            target = {};
        }
        if (i === length) {
            target = this;
            i--;
        }
        for (; i < length; i++) {
            if ((options = arguments[i]) != null) {
                for (name in options) {
                    copy = options[name];
                    if (name === '__proto__' || target === copy) {
                        continue;
                    }
                    if (deep && copy && (this.isPlainObject(copy) || (copyIsArray = Array.isArray(copy)))) {
                        src = target[name];
                        if (copyIsArray && !Array.isArray(src)) {
                            clone = [];
                        } else if (!copyIsArray && !this.isPlainObject(src)) {
                            clone = {};
                        } else {
                            clone = src;
                        }
                        copyIsArray = false;
                        target[name] = this.extend(deep, clone, copy);
                    } else if (copy !== undefined) {
                        target[name] = copy;
                    }
                }
            }
        }
        return target;
    },
    //对象或数组是否相同|深度
    comparison(parmA, parmB) {
        if (parmA === parmB) {
            return true;
        }
        if (this.isPlainObject(parmA) && this.isPlainObject(parmB) && Object.keys(parmA).length === Object.keys(parmB).length) {
            for (let key in parmA) {
                if (parmB.hasOwnProperty(key)) {
                    if (!this.comparison(parmA[key], parmB[key])) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        } else if (Array.isArray(parmA) && Array.isArray(parmA) && parmA.length === parmB.length) {
            for (let i = 0; i < parmA.length; i++) {
                if (!this.comparison(parmA[i], parmB[i])) {
                    return false;
                }
            }
        } else {
            return false;
        }
        return true;
    }
};
Vue.prototype.$lib = lib;
