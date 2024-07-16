import Vue from "vue";
import Router from "vue-router";
import Login from "@/views/Login";
//通用
const Home = () => import(/* webpackChunkName: 'a'*/ "@/views/Home");
const Main = () => import(/* webpackChunkName: 'b'*/ "@/views/main/Main");
const Guide = () => import(/* webpackChunkName: 'b'*/ "@/views/main/Guide");
//采购
const Bor = () => import(/* webpackChunkName: 'c'*/ "@/views/purchase/Bor");
const BorForm = () =>
  import(/* webpackChunkName: 'c'*/ "@/views/purchase/BorForm");
const Buy = () => import(/* webpackChunkName: 'c'*/ "@/views/purchase/Buy");
const BuyForm = () =>
  import(/* webpackChunkName: 'c'*/ "@/views/purchase/BuyForm");
const Bre = () => import(/* webpackChunkName: 'c'*/ "@/views/purchase/Bre");
const BreForm = () =>
  import(/* webpackChunkName: 'c'*/ "@/views/purchase/BreForm");
//销售
const Sor = () => import(/* webpackChunkName: 'd'*/ "@/views/sale/Sor");
const SorForm = () => import(/* webpackChunkName: 'd'*/ "@/views/sale/SorForm");
const Sell = () => import(/* webpackChunkName: 'd'*/ "@/views/sale/Sell");
const SellForm = () =>
  import(/* webpackChunkName: 'd'*/ "@/views/sale/SellForm");
const Sre = () => import(/* webpackChunkName: 'd'*/ "@/views/sale/Sre");
const SreForm = () => import(/* webpackChunkName: 'd'*/ "@/views/sale/SreForm");

//仓库
const Stock = () => import(/* webpackChunkName: 'f'*/ "@/views/room/Stock");
const Inventory = () =>
  import(/* webpackChunkName: 'f'*/ "@/views/room/Inventory");
const Batch = () => import(/* webpackChunkName: 'f'*/ "@/views/room/Batch");
const Serial = () => import(/* webpackChunkName: 'f'*/ "@/views/room/Serial");
const Swap = () => import(/* webpackChunkName: 'f'*/ "@/views/room/Swap");
const SwapForm = () =>
  import(/* webpackChunkName: 'f'*/ "@/views/room/SwapForm");
const Entry = () => import(/* webpackChunkName: 'f'*/ "@/views/room/Entry");
const EntryForm = () =>
  import(/* webpackChunkName: 'f'*/ "@/views/room/EntryForm");
const Extry = () => import(/* webpackChunkName: 'f'*/ "@/views/room/Extry");
const ExtryForm = () =>
  import(/* webpackChunkName: 'f'*/ "@/views/room/ExtryForm");
//资金
const Imy = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/Imy");
const ImyForm = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/ImyForm");
const Omy = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/Omy");
const OmyForm = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/OmyForm");
const Bill = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/Bill");
const BillForm = () =>
  import(/* webpackChunkName: 'g'*/ "@/views/fund/BillForm");
const Allot = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/Allot");
const AllotForm = () =>
  import(/* webpackChunkName: 'g'*/ "@/views/fund/AllotForm");
const Ice = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/Ice");
const IceForm = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/IceForm");
const Oce = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/Oce");
const OceForm = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/OceForm");
const Cost = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/Cost");
const CostForm = () =>
  import(/* webpackChunkName: 'g'*/ "@/views/fund/CostForm");
const Invoice = () => import(/* webpackChunkName: 'g'*/ "@/views/fund/Invoice");
const InvoiceForm = () =>
  import(/* webpackChunkName: 'g'*/ "@/views/fund/InvoiceForm");
//报表-采购
const Btt = () => import(/* webpackChunkName: 'h'*/ "@/views/sheet/brt/Btt");
const Blt = () => import(/* webpackChunkName: 'h'*/ "@/views/sheet/brt/Blt");
const Bsy = () => import(/* webpackChunkName: 'h'*/ "@/views/sheet/brt/Bsy");
const Bbt = () => import(/* webpackChunkName: 'h'*/ "@/views/sheet/brt/Bbt");
const Bot = () => import(/* webpackChunkName: 'h'*/ "@/views/sheet/brt/Bot");
//报表-销售
const Stt = () => import(/* webpackChunkName: 'i'*/ "@/views/sheet/srt/Stt");
const Slt = () => import(/* webpackChunkName: 'i'*/ "@/views/sheet/srt/Slt");
const Ssy = () => import(/* webpackChunkName: 'i'*/ "@/views/sheet/srt/Ssy");
const Sbt = () => import(/* webpackChunkName: 'i'*/ "@/views/sheet/srt/Sbt");

//报表-销售
const Mot = () => import(/* webpackChunkName: 'k'*/ "@/views/sheet/mrt/Mot");
const Mpt = () => import(/* webpackChunkName: 'k'*/ "@/views/sheet/mrt/Mpt");
//报表-仓库
const Wbs = () => import(/* webpackChunkName: 'l'*/ "@/views/sheet/wrf/Wbs");
const Wds = () => import(/* webpackChunkName: 'l'*/ "@/views/sheet/wrf/Wds");
const Wss = () => import(/* webpackChunkName: 'l'*/ "@/views/sheet/wrf/Wss");
//报表-资金
const Cbf = () => import(/* webpackChunkName: 'm'*/ "@/views/sheet/crt/Cbf");
const Crs = () => import(/* webpackChunkName: 'm'*/ "@/views/sheet/crt/Crs");
const Cps = () => import(/* webpackChunkName: 'm'*/ "@/views/sheet/crt/Cps");
const Cct = () => import(/* webpackChunkName: 'm'*/ "@/views/sheet/crt/Cct");
const Cst = () => import(/* webpackChunkName: 'm'*/ "@/views/sheet/crt/Cst");
const Cos = () => import(/* webpackChunkName: 'm'*/ "@/views/sheet/crt/Cos");
const Cit = () => import(/* webpackChunkName: 'm'*/ "@/views/sheet/crt/Cit");
const Cds = () => import(/* webpackChunkName: 'm'*/ "@/views/sheet/crt/Cds");
//基础资料
const Customer = () =>
  import(/* webpackChunkName: 'n'*/ "@/views/system/base/Customer");
const Supplier = () =>
  import(/* webpackChunkName: 'n'*/ "@/views/system/base/Supplier");
const Warehouse = () =>
  import(/* webpackChunkName: 'n'*/ "@/views/system/base/Warehouse");
const Account = () =>
  import(/* webpackChunkName: 'n'*/ "@/views/system/base/Account");
const Goods = () =>
  import(/* webpackChunkName: 'n'*/ "@/views/system/base/Goods");
//辅助资料
const Category = () =>
  import(/* webpackChunkName: 'o'*/ "@/views/system/assist/Category");
const Iet = () =>
  import(/* webpackChunkName: 'o'*/ "@/views/system/assist/Iet");
const Attribute = () =>
  import(/* webpackChunkName: 'o'*/ "@/views/system/assist/Attribute");
const Code = () =>
  import(/* webpackChunkName: 'o'*/ "@/views/system/assist/Code");
const Often = () =>
  import(/* webpackChunkName: 'o'*/ "@/views/system/assist/Often");
//高级设置
const Sys = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/Sys");
const Frame = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/Frame");
const Role = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/Role");
const User = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/User");
const People = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/People");
const Log = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/Log");
const Period = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/Period");
const Summary = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/Summary");
const Backup = () =>
  import(/* webpackChunkName: 'p'*/ "@/views/system/senior/Backup");
//系统配置
const Menu = () => import(/* webpackChunkName: 'q'*/ "@/views/develop/Menu");
const Field = () => import(/* webpackChunkName: 'q'*/ "@/views/develop/Field");

//404
const NotFound = () =>
  import(/* webpackChunkName: 'r'*/ "@/views/main/NotFound");

Vue.use(Router);
const example = new Router({
  routes: [
    {
      path: "/",
      name: "Login",
      component: Login,
      meta: {
        title: "登录",
      },
    },
    {
      path: "/home",
      component: Home,
      children: [
        {
          path: "/",
          name: "main",
          component: Main,
          meta: {
            title: "首页",
          },
        },
        {
          path: "guide",
          name: "guide",
          component: Guide,
          meta: {
            title: "新手向导",
          },
        },
      ],
    },
    {
      path: "/purchase",
      component: Home,
      children: [
        {
          path: "bor",
          name: "bor",
          component: Bor,
          meta: {
            title: "采购订单",
          },
        },
        {
          path: "borForm",
          name: "borForm",
          component: BorForm,
          meta: {
            title: "采购订单报表",
          },
        },
        {
          path: "buy",
          name: "buy",
          component: Buy,
          meta: {
            title: "采购单",
          },
        },
        {
          path: "buyForm",
          name: "buyForm",
          component: BuyForm,
          meta: {
            title: "采购单报表",
          },
        },
        {
          path: "bre",
          name: "bre",
          component: Bre,
          meta: {
            title: "采购退货单",
          },
        },
        {
          path: "breForm",
          name: "breForm",
          component: BreForm,
          meta: {
            title: "采购退货单报表",
          },
        },
      ],
    },
    {
      path: "/sale",
      component: Home,
      children: [
        {
          path: "sor",
          name: "sor",
          component: Sor,
          meta: {
            title: "销售订单",
          },
        },
        {
          path: "sorForm",
          name: "sorForm",
          component: SorForm,
          meta: {
            title: "销售订单报表",
          },
        },
        {
          path: "sell",
          name: "sell",
          component: Sell,
          meta: {
            title: "销售单",
          },
        },
        {
          path: "sellForm",
          name: "sellForm",
          component: SellForm,
          meta: {
            title: "销售单报表",
          },
        },
        {
          path: "sre",
          name: "sre",
          component: Sre,
          meta: {
            title: "销售退货单",
          },
        },
        {
          path: "sreForm",
          name: "sreForm",
          component: SreForm,
          meta: {
            title: "销售退货单报表",
          },
        },
      ],
    },
    {
      path: "/room",
      component: Home,
      children: [
        {
          path: "stock",
          name: "stock",
          component: Stock,
          meta: {
            title: "库存查询",
          },
        },
        {
          path: "inventory",
          name: "inventory",
          component: Inventory,
          meta: {
            title: "盘点单",
          },
        },
        {
          path: "batch",
          name: "batch",
          component: Batch,
          meta: {
            title: "批次查询",
          },
        },
        {
          path: "serial",
          name: "serial",
          component: Serial,
          meta: {
            title: "序列查询",
          },
        },
        {
          path: "swap",
          name: "swap",
          component: Swap,
          meta: {
            title: "调拨单",
          },
        },
        {
          path: "swapForm",
          name: "swapForm",
          component: SwapForm,
          meta: {
            title: "调拨单报表",
          },
        },
        {
          path: "entry",
          name: "entry",
          component: Entry,
          meta: {
            title: "其它入库单",
          },
        },
        {
          path: "entryForm",
          name: "entryForm",
          component: EntryForm,
          meta: {
            title: "其它入库单报表",
          },
        },
        {
          path: "extry",
          name: "extry",
          component: Extry,
          meta: {
            title: "其它出库单",
          },
        },
        {
          path: "extryForm",
          name: "extryForm",
          component: ExtryForm,
          meta: {
            title: "其它出库单报表",
          },
        },
      ],
    },
    {
      path: "/fund",
      component: Home,
      children: [
        {
          path: "imy",
          name: "imy",
          component: Imy,
          meta: {
            title: "收款单",
          },
        },
        {
          path: "imyForm",
          name: "imyForm",
          component: ImyForm,
          meta: {
            title: "收款单报表",
          },
        },
        {
          path: "omy",
          name: "omy",
          component: Omy,
          meta: {
            title: "付款单",
          },
        },
        {
          path: "omyForm",
          name: "omyForm",
          component: OmyForm,
          meta: {
            title: "付款单报表",
          },
        },
        {
          path: "bill",
          name: "bill",
          component: Bill,
          meta: {
            title: "核销单",
          },
        },
        {
          path: "billForm",
          name: "billForm",
          component: BillForm,
          meta: {
            title: "核销单报表",
          },
        },
        {
          path: "allot",
          name: "allot",
          component: Allot,
          meta: {
            title: "转账单",
          },
        },
        {
          path: "allotForm",
          name: "allotForm",
          component: AllotForm,
          meta: {
            title: "转账单报表",
          },
        },
        {
          path: "ice",
          name: "ice",
          component: Ice,
          meta: {
            title: "其它收入单",
          },
        },
        {
          path: "iceForm",
          name: "iceForm",
          component: IceForm,
          meta: {
            title: "其他收入单报表",
          },
        },
        {
          path: "oce",
          name: "oce",
          component: Oce,
          meta: {
            title: "其它支出单",
          },
        },
        {
          path: "oceForm",
          name: "oceForm",
          component: OceForm,
          meta: {
            title: "其他支出单报表",
          },
        },
        {
          path: "cost",
          name: "cost",
          component: Cost,
          meta: {
            title: "购销费用",
          },
        },
        {
          path: "costForm",
          name: "costForm",
          component: CostForm,
          meta: {
            title: "购销费用报表",
          },
        },
        {
          path: "invoice",
          name: "invoice",
          component: Invoice,
          meta: {
            title: "购销发票",
          },
        },
        {
          path: "invoiceForm",
          name: "invoiceForm",
          component: InvoiceForm,
          meta: {
            title: "购销发票报表",
          },
        },
      ],
    },
    {
      path: "/sheet",
      component: Home,
      children: [
        {
          path: "btt",
          name: "btt",
          component: Btt,
          meta: {
            title: "采购订单跟踪表",
          },
        },
        {
          path: "blt",
          name: "blt",
          component: Blt,
          meta: {
            title: "采购明细表",
          },
        },
        {
          path: "bsy",
          name: "bsy",
          component: Bsy,
          meta: {
            title: "采购汇总表",
          },
        },
        {
          path: "bbt",
          name: "bbt",
          component: Bbt,
          meta: {
            title: "采购付款表",
          },
        },
        {
          path: "bot",
          name: "bot",
          component: Bot,
          meta: {
            title: "采购排行表",
          },
        },
        {
          path: "stt",
          name: "stt",
          component: Stt,
          meta: {
            title: "销售订单跟踪表",
          },
        },
        {
          path: "slt",
          name: "slt",
          component: Slt,
          meta: {
            title: "销售明细表",
          },
        },
        {
          path: "ssy",
          name: "ssy",
          component: Ssy,
          meta: {
            title: "销售汇总表",
          },
        },
        {
          path: "sbt",
          name: "sbt",
          component: Sbt,
          meta: {
            title: "销售收款表",
          },
        },
        {
          path: "mot",
          name: "mot",
          component: Mot,
          meta: {
            title: "销售排行表",
          },
        },
        {
          path: "mpt",
          name: "mpt",
          component: Mpt,
          meta: {
            title: "销售利润表",
          },
        },
        {
          path: "wbs",
          name: "wbs",
          component: Wbs,
          meta: {
            title: "商品库存余额表",
          },
        },
        {
          path: "wds",
          name: "wds",
          component: Wds,
          meta: {
            title: "商品收发明细表",
          },
        },
        {
          path: "wss",
          name: "wss",
          component: Wss,
          meta: {
            title: "商品收发汇总表",
          },
        },
        {
          path: "cbf",
          name: "cbf",
          component: Cbf,
          meta: {
            title: "现金银行报表",
          },
        },
        {
          path: "crs",
          name: "crs",
          component: Crs,
          meta: {
            title: "应收账款明细表",
          },
        },
        {
          path: "cps",
          name: "cps",
          component: Cps,
          meta: {
            title: "应付账款明细表",
          },
        },
        {
          path: "cct",
          name: "cct",
          component: Cct,
          meta: {
            title: "客户对账单",
          },
        },
        {
          path: "cos",
          name: "cos",
          component: Cos,
          meta: {
            title: "其它收支明细表",
          },
        },
        {
          path: "cst",
          name: "cst",
          component: Cst,
          meta: {
            title: "供应商对账单",
          },
        },
        {
          path: "cit",
          name: "cit",
          component: Cit,
          meta: {
            title: "利润表",
          },
        },
        {
          path: "cds",
          name: "cds",
          component: Cds,
          meta: {
            title: "往来单位欠款表",
          },
        },
      ],
    },
    {
      path: "/system",
      component: Home,
      children: [
        {
          path: "customer",
          name: "customer",
          component: Customer,
          meta: {
            title: "客户管理",
          },
        },
        {
          path: "supplier",
          name: "supplier",
          component: Supplier,
          meta: {
            title: "供应商管理",
          },
        },
        {
          path: "warehouse",
          name: "warehouse",
          component: Warehouse,
          meta: {
            title: "仓库管理",
          },
        },
        {
          path: "account",
          name: "account",
          component: Account,
          meta: {
            title: "资金账户",
          },
        },
        {
          path: "goods",
          name: "goods",
          component: Goods,
          meta: {
            title: "商品管理",
          },
        },
        {
          path: "category",
          name: "category",
          component: Category,
          meta: {
            title: "商品类别",
          },
        },
        {
          path: "iet",
          name: "iet",
          component: Iet,
          meta: {
            title: "收支类别",
          },
        },
        {
          path: "attribute",
          name: "attribute",
          component: Attribute,
          meta: {
            title: "辅助属性",
          },
        },
        {
          path: "code",
          name: "code",
          component: Code,
          meta: {
            title: "条码管理",
          },
        },
        {
          path: "often",
          name: "often",
          component: Often,
          meta: {
            title: "常用功能",
          },
        },
        {
          path: "sys",
          name: "sys",
          component: Sys,
          meta: {
            title: "系统设置",
          },
        },
        {
          path: "frame",
          name: "frame",
          component: Frame,
          meta: {
            title: "组织机构",
          },
        },
        {
          path: "role",
          name: "role",
          component: Role,
          meta: {
            title: "用户角色",
          },
        },
        {
          path: "user",
          name: "user",
          component: User,
          meta: {
            title: "用户管理",
          },
        },
        {
          path: "people",
          name: "people",
          component: People,
          meta: {
            title: "人员管理",
          },
        },
        {
          path: "log",
          name: "log",
          component: Log,
          meta: {
            title: "操作日志",
          },
        },
        {
          path: "period",
          name: "period",
          component: Period,
          meta: {
            title: "结账管理",
          },
        },
        {
          path: "summary",
          name: "summary",
          component: Summary,
          meta: {
            title: "数据校准",
          },
        },
        {
          path: "backup",
          name: "backup",
          component: Backup,
          meta: {
            title: "数据备份",
          },
        },
      ],
    },
    {
      path: "/develop",
      component: Home,
      children: [
        {
          path: "menu",
          name: "menu",
          component: Menu,
          meta: {
            title: "菜单管理",
          },
        },
        {
          path: "field",
          name: "field",
          component: Field,
          meta: {
            title: "表单字段",
          },
        },
      ],
    },
    {
      path: "*",
      name: "notFound",
      component: NotFound,
      meta: {
        title: "404 NotFound",
      },
    },
  ],
});
//路由守卫
example.beforeEach((to, from, next) => {
  document.title = to.meta.title; //设置标题名称
  next();
});
export default example;
