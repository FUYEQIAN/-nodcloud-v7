<template>
  <div class="inputGroup">
    <input class="ag-cell-edit-input" type="text" v-model="value" ref="input" />
    <el-popover
      class="popoverTag"
      popper-class="agPricePopover"
      trigger="click"
      @show="getPrice"
    >
      <div slot="reference">
        <i class="el-icon-collection-tag" @click="showPopover"></i>
      </div>
      <p class="priceText" @click="setPrice">最近价格:{{ price }}</p>
    </el-popover>
  </div>
</template>
<script>
//内容组件|价格|最近价格
import Vue from "vue";
export default Vue.extend({
  name: "Price",
  data() {
    return {
      value: "",
      price: 0,
    };
  },
  created() {
    this.value = this.params.value;
  },
  computed: {
    hasData() {
      if (this.params.node.data.key == null) {
        return false;
      } else {
        return true;
      }
    },
    modelText() {
      let model = this.params.dispose.model;
      if (["bor", "buy", "bre", "sor", "sell", "sre"].indexOf(model) == -1) {
        return "未识别的模块";
      } else {
        if (["bor", "buy", "bre"].indexOf(model) != -1) {
          return "供应商";
        } else {
          return "客户";
        }
      }
    },
  },
  methods: {
    //显示组件
    showPopover(e) {
      if (this.params.dispose.source() == null) {
        this.$message({
          type: "warning",
          message: "请先选择" + this.modelText,
        });
        e.stopPropagation();
      } else {
        let data = this.params.node.data;
        if (data.unitData.length > 0 && data.unit == "点击选择") {
          this.$message({ type: "warning", message: "请先选择单位" });
          e.stopPropagation();
        }
      }
    },
    //获取数据
    getPrice() {
      let dispose = this.params.dispose;
      let data = this.params.node.data;
      this.$axios
        .post("service/recentPrice", {
          model: dispose.model,
          source: dispose.source(),
          goods: data.key,
          attr: data.attr,
          unit: data.unit,
        })
        .then((result) => {
          if (result.state == "success") {
            this.price = result.info;
          } else if (result.state == "error") {
            this.$message({
              type: "warning",
              message: result.info,
            });
          } else {
            this.$message({
              type: "error",
              message: "[ ERROR ] 服务器响应超时!",
            });
          }
        });
    },
    //设置价格
    setPrice() {
      if (this.price != 0) {
        this.value = this.price;
        this.$el.parentNode.focus();
        this.params.api.stopEditing();
      }
    },
    //返回内容
    getValue() {
      return this.value;
    },
    //编辑状态
    isCancelBeforeStart() {
      return !this.hasData;
    },
  },
  mounted() {
    if (this.hasData) {
      this.$nextTick(() => {
        this.$refs.input.focus();
        this.$refs.input.select();
      });
    }
  },
});
</script>
<style>
.agPricePopover {
  min-width: auto;
  padding: 6px;
}
</style>
<style scoped>
.inputGroup {
  position: relative;
  width: 100%;
  height: 100%;
}
.popoverTag {
  position: absolute;
  right: 6px;
  top: 6px;
  line-height: initial;
}
.priceText {
  font-size: 12px;
  cursor: default;
}
</style>
