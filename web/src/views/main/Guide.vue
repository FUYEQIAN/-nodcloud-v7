<template>
  <div class="guide area">
    <el-tabs v-model="active">
      <el-tab-pane name="base">
        <template slot="label">
          <span><i class="el-icon-monitor"></i> 基础数据</span>
        </template>
        <div class="draw" id="base">
          <h2>配置如下资料数据</h2>
          <el-row :gutter="32">
            <el-col :span="8">
              <div class="group">
                <b>01 软件配置</b>
                <div class="item" @click="switchPage('sys')">
                  <img src="/static/images/guide/svg/0-0-0.svg" />
                  <span>系统设置</span>
                </div>
                <div class="item" @click="switchPage('frame')">
                  <img src="/static/images/guide/svg/0-0-1.svg" />
                  <span>组织机构</span>
                </div>
                <div class="item" @click="switchPage('role')">
                  <img src="/static/images/guide/svg/0-0-2.svg" />
                  <span>用户角色</span>
                </div>
                <div class="item" @click="switchPage('user')">
                  <img src="/static/images/guide/svg/0-0-3.svg" />
                  <span>用户管理</span>
                </div>
                <div class="item" @click="switchPage('people')">
                  <img src="/static/images/guide/svg/0-0-4.svg" />
                  <span>人员管理</span>
                </div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="group">
                <b>02 数据资料</b>
                <div class="item" @click="switchPage('customer')">
                  <img src="/static/images/guide/svg/0-1-0.svg" />
                  <span>客户管理</span>
                </div>
                <div class="item" @click="switchPage('supplier')">
                  <img src="/static/images/guide/svg/0-1-1.svg" />
                  <span>供应商管理</span>
                </div>
                <div class="item" @click="switchPage('account')">
                  <img src="/static/images/guide/svg/0-1-2.svg" />
                  <span>资金账户</span>
                </div>
                <div class="item" @click="switchPage('warehouse')">
                  <img src="/static/images/guide/svg/0-1-3.svg" />
                  <span>仓库管理</span>
                </div>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="group">
                <b>03 商品信息</b>
                <div class="item" @click="switchPage('goods')">
                  <img src="/static/images/guide/svg/0-2-0.svg" />
                  <span>商品管理</span>
                </div>
                <div class="item" @click="switchPage('category')">
                  <img src="/static/images/guide/svg/0-2-1.svg" />
                  <span>商品类别</span>
                </div>
                <div class="item" @click="switchPage('attribute')">
                  <img src="/static/images/guide/svg/0-2-2.svg" />
                  <span>辅助属性</span>
                </div>
                <div class="item" @click="switchPage('code')">
                  <img src="/static/images/guide/svg/0-2-3.svg" />
                  <span>条码管理</span>
                </div>
              </div>
            </el-col>
          </el-row>
        </div>
      </el-tab-pane>
      <el-tab-pane name="purchase">
        <template slot="label">
          <span><i class="el-icon-shopping-cart-2"></i> 采购流程</span>
        </template>
        <div class="draw" id="purchase">
          <h2>采购业务流程</h2>
          <el-row :gutter="100">
            <el-col :span="6">
              <div class="item" @click="switchPage('bor')">
                <img src="/static/images/guide/svg/1-0-0.svg" />
                <span>采购订单</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('buy')">
                <img src="/static/images/guide/svg/1-0-1.svg" />
                <span>采购单</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('omy')">
                <img src="/static/images/guide/svg/1-0-2.svg" />
                <span>付款单</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('invoice')">
                <img src="/static/images/guide/svg/1-0-3.svg" />
                <span>购销发票</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('cost')">
                <img src="/static/images/guide/svg/1-1-0.svg" />
                <span>购销费用</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('bre')">
                <img src="/static/images/guide/svg/1-1-1.svg" />
                <span>采购退货单</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('bill')">
                <img src="/static/images/guide/svg/1-1-2.svg" />
                <span>核销单</span>
              </div>
            </el-col>
          </el-row>
        </div>
      </el-tab-pane>
      <el-tab-pane name="sale">
        <template slot="label">
          <span><i class="el-icon-shopping-cart-full"></i> 销售流程</span>
        </template>
        <div class="draw" id="sale">
          <h2>销售业务流程</h2>
          <el-row :gutter="100">
            <el-col :span="6">
              <div class="item" @click="switchPage('sor')">
                <img src="/static/images/guide/svg/2-0-0.svg" />
                <span>销售订单</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('sell')">
                <img src="/static/images/guide/svg/2-0-1.svg" />
                <span>销售单</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('imy')">
                <img src="/static/images/guide/svg/2-0-2.svg" />
                <span>收款单</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('invoice')">
                <img src="/static/images/guide/svg/2-0-3.svg" />
                <span>购销发票</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('cost')">
                <img src="/static/images/guide/svg/2-1-0.svg" />
                <span>购销费用</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('sre')">
                <img src="/static/images/guide/svg/2-1-1.svg" />
                <span>销售退货单</span>
              </div>
            </el-col>
            <el-col :span="6">
              <div class="item" @click="switchPage('bill')">
                <img src="/static/images/guide/svg/2-1-2.svg" />
                <span>核销单</span>
              </div>
            </el-col>
          </el-row>
        </div>
      </el-tab-pane>

      <el-tab-pane name="room">
        <template slot="label">
          <span><i class="el-icon-house"></i> 仓库流程</span>
        </template>
        <div class="draw" id="room">
          <h2>仓储业务流程</h2>
          <el-row :gutter="160">
            <el-col :span="8">
              <div class="item" @click="switchPage('entry')">
                <img src="/static/images/guide/svg/4-0-0.svg" />
                <span>其它入库单</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="item" @click="switchPage('inventory')">
                <img src="/static/images/guide/svg/4-0-1.svg" />
                <span>盘点单</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="item" @click="switchPage('extry')">
                <img src="/static/images/guide/svg/4-0-2.svg" />
                <span>其它出库单</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="item" @click="switchPage('swap')">
                <img src="/static/images/guide/svg/4-1-0.svg" />
                <span>调拨单</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="item" @click="switchPage('stock')">
                <img src="/static/images/guide/svg/4-1-1.svg" />
                <span>库存查询</span>
              </div>
            </el-col>
          </el-row>
        </div>
      </el-tab-pane>
      <el-tab-pane name="fund">
        <template slot="label">
          <span><i class="el-icon-coin"></i> 资金流程</span>
        </template>
        <div class="draw" id="fund">
          <h2>资金业务流程</h2>
          <el-row :gutter="160">
            <el-col :span="8">
              <div class="item" @click="switchPage('imy')">
                <img src="/static/images/guide/svg/5-0-0.svg" />
                <span>收款单</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="item" @click="switchPage('ice')">
                <img src="/static/images/guide/svg/5-0-1.svg" />
                <span>其它收入单</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="item" @click="switchPage('allot')">
                <img src="/static/images/guide/svg/5-0-2.svg" />
                <span>转账单</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="item" @click="switchPage('omy')">
                <img src="/static/images/guide/svg/5-1-0.svg" />
                <span>付款单</span>
              </div>
            </el-col>
            <el-col :span="8">
              <div class="item" @click="switchPage('oce')">
                <img src="/static/images/guide/svg/5-1-1.svg" />
                <span>其它支出单</span>
              </div>
            </el-col>
          </el-row>
        </div>
      </el-tab-pane>
    </el-tabs>
  </div>
</template>
<script>
export default {
  name: "Guide",
  data() {
    return {
      active: "base",
    };
  },
  created() {},
  mounted() {},
  computed: {
    //读取数据中心
    store() {
      return this.$store.state;
    },
  },
  methods: {
    //切换页面
    switchPage(key) {
      this.$bus.emit("switchPage", key, true); //切换页面
    },
  },
};
</script>

<style scoped>
.area {
  min-width: 1048px;
}

.draw {
  position: relative;
  margin-top: 56px !important;
}
/* 基础 */
#base {
  width: 1024px;
  height: 360px;
  margin: 0 auto;
  background: url(/static/images/guide/img/0.png);
  background-repeat: no-repeat;
  background-size: 100% 100%;
}
#base .group {
  padding: 0 12px;
}
#base b {
  line-height: 48px;
  color: #333;
  font-weight: initial;
}
#base img {
  width: 28px;
  margin-right: 12px;
}
#base span {
  line-height: 28px;
}

#purchase {
  width: 1024px;
  height: 175px;
  margin: 0 auto;
  background: url(/static/images/guide/img/1.png);
  background-repeat: no-repeat;
  background-size: 100% 100%;
}
#purchase .item {
  margin-bottom: 60px;
}
#purchase img {
  width: 36px;
  margin-right: 12px;
}
#purchase span {
  line-height: 36px;
}

#sale {
  width: 1024px;
  height: 175px;
  margin: 0 auto;
  background: url(/static/images/guide/img/2.png);
  background-repeat: no-repeat;
  background-size: 100% 100%;
}
#sale .item {
  margin-bottom: 60px;
}
#sale img {
  width: 36px;
  margin-right: 12px;
}
#sale span {
  line-height: 36px;
}

#retail {
  width: 1024px;
  height: 282px;
  margin: 0 auto;
  background: url(/static/images/guide/img/3.png);
  background-repeat: no-repeat;
  background-size: 100% 100%;
}
#retail .item {
  margin-bottom: 60px;
}
#retail img {
  width: 36px;
  margin-right: 12px;
}
#retail span {
  line-height: 36px;
}

#room {
  width: 1024px;
  height: 282px;
  margin: 0 auto;
  background: url(/static/images/guide/img/4.png);
  background-repeat: no-repeat;
  background-size: 100% 100%;
}
#room .item {
  margin-bottom: 60px;
}
#room img {
  width: 36px;
  margin-right: 12px;
}
#room span {
  line-height: 36px;
}

#fund {
  width: 1024px;
  height: 175px;
  margin: 0 auto;
  background: url(/static/images/guide/img/5.png);
  background-repeat: no-repeat;
  background-size: 100% 100%;
}
#fund .item {
  margin-bottom: 60px;
}
#fund img {
  width: 36px;
  margin-right: 12px;
}
#fund span {
  line-height: 36px;
}

h2 {
  position: absolute;
  left: 0;
  right: 0;
  text-align: center;
  top: -42px;
  font-size: 16px;
  letter-spacing: 1px;
  font-weight: inherit;
  color: #333;
}
/* 通用 */
.item {
  display: flex;
  margin-bottom: 12px;
  background: #fff;
  padding: 9px 12px;
  box-sizing: border-box;
  border-radius: 4px;
  cursor: pointer;
  border: 1px solid #e6e6e6;
  box-shadow: 0 2px 8px 0 rgb(0 0 0 / 8%);
}
.item:hover {
  box-shadow: 0 4px 16px 0 rgb(0 0 0 / 10%);
}
</style>
