<template>
  <div class="imy bill">
    <el-form
      :model="form"
      :rules="rules"
      ref="form"
      :inline="true"
      :hide-required-asterisk="true"
      style="height: 100%"
    >
      <el-form-item label="客户" prop="customer">
        <template slot="label">
          <span @click="switchPage('customer')">客户</span>
        </template>
        <nodList
          v-model="form.customer"
          placeholder="请选择客户"
          action="service/customerRecord"
          scene="customer"
        ></nodList>
      </el-form-item>
      <el-form-item label="单据日期" prop="time">
        <el-date-picker
          v-model="form.time"
          value-format="yyyy-MM-dd"
          type="date"
          placeholder="请选择单据日期"
        ></el-date-picker>
      </el-form-item>
      <el-form-item label="单据编号" prop="number">
        <el-input
          placeholder="请输入单据编号"
          v-model="form.number"
          clearable
        ></el-input>
      </el-form-item>
      <el-divider class="fistDivider"></el-divider>
      <ag-grid-vue
        class="ag-theme-balham"
        :context="grid.context"
        :defaultColDef="grid.coldef"
        :gridOptions="grid.options"
        :columnDefs="grid.column"
        :getRowNodeId="grid.getRowNodeId"
        :frameworkComponents="grid.components"
        :tabToNextCell="$grid.gridTabToNextCell"
        @addRow="pushGrid([{}], true)"
        style="height: calc(100% - 256px)"
        v-madeAgGrid
      ></ag-grid-vue>
      <Status :config="grid.status" :source="info">
        <img
          v-if="form.examine == 1"
          class="billExamine"
          src="/static/images/lib/examine.png"
        />
      </Status>
      <el-divider></el-divider>
      <LineFeed tag=".el-form-item" :rule="3">
        <el-form-item label="单据金额">
          <el-input
            placeholder="请输入单据金额"
            v-model="form.total"
            :disabled="true"
          ></el-input>
        </el-form-item>
        <el-form-item label="关联人员">
          <nodList
            v-model="form.people"
            placeholder="请选择关联人员"
            action="service/peopleRecord"
            scene="people"
          ></nodList>
        </el-form-item>
        <el-form-item label="单据附件">
          <NodUpload
            v-model="form.file"
            :action="$base.web + 'imy/upload'"
          ></NodUpload>
        </el-form-item>
        <FieldForm
          style="display: initial"
          v-model="form.more"
          rule="imy"
          prefix="more."
        ></FieldForm>
        <el-form-item label="备注信息">
          <el-input
            placeholder="请输入备注信息"
            v-model="form.data"
            clearable
          ></el-input>
        </el-form-item>
      </LineFeed>
    </el-form>
    <div class="layerBtn">
      <el-button-group>
        <template
          v-if="
            form.id == 0 ||
            (form.examine == 0 && $lib.getUserRoot(store.root, 'imy', 'edit'))
          "
        >
          <el-button type="info" @click="save">保存</el-button>
        </template>
        <template v-if="form.id != 0">
          <template v-if="$lib.getUserRoot(store.root, 'imy', 'examine')">
            <el-button type="info" @click="examine">{{
              form.examine == 0 ? "审核" : "反审核"
            }}</el-button>
          </template>
        </template>
        <template v-if="source == null">
          <el-button type="info" @click="reload">刷新</el-button>
        </template>
      </el-button-group>
    </div>
  </div>
</template>
<script>
import { AgGridVue } from "ag-grid-vue";
import Dispose from "@/components/ag-grid/Dispose";
import Handle from "@/components/ag-grid/Handle";
import Fat from "@/components/ag-grid/Fat";
import Status from "@/components/ag-grid/Status";
import LineFeed from "@/components/lib/LineFeed";
import NodList from "@/components/lib/NodList";
import NodUpload from "@/components/lib/NodUpload";
import FieldForm from "@/components/lib/FieldForm";

export default {
  name: "Imy",
  components: {
    AgGridVue,
    Status,
    LineFeed,
    NodList,
    NodUpload,
    FieldForm,
  },
  props: {
    source: {
      default: null,
    },
  },
  data() {
    return {
      form: {
        id: 0,
        customer: null,
        time: "",
        number: "",
        total: 0,
        people: null,
        file: [],
        data: "",
        examine: 0,
        more: {},
      },
      rules: {
        customer: {
          required: true,
          message: "请选择客户",
          trigger: "change",
        },
        time: {
          required: true,
          message: "请选择单据日期",
          trigger: "change",
        },
        number: {
          required: true,
          message: "请输入单据编号",
          trigger: "blur",
        },
      },
      info: [],
      grid: {
        options: {
          onGridReady: () => {
            this.$grid.addGridRow(this.grid.options);
          },
          onCellEditingStopped: (grid) => {
            grid.context.runHandleGrid();
          },
        },
        context: {
          row: {
            uniqid: this.$lib.getUniqid(),
            accountId: null,
            account: "点击选择",
            money: "",
            settle: "",
            data: "",
          },
          runHandleGrid: () => {
            if (this.grid.options.api == null) {
              return false;
            } else {
              this.handleGrid();
            }
          },
        },
        coldef: {
          suppressMenu: false,
          resizable: true,
          singleClickEdit: true,
          suppressMovable: true,
        },
        components: {
          dispose: Dispose,
          handle: Handle,
          fat: Fat,
        },
        getRowNodeId(row) {
          return row.uniqid;
        },
        column: [
          {
            headerName: "序号",
            width: 50,
            field: "sequence",
            headerComponent: "dispose",
            headerComponentParams: { dispose: { key: "imy" } },
            valueGetter: (params) => params.node.rowIndex + 1,
            hide: false,
            dispose: false,
          },
          {
            headerName: "操作",
            width: 50,
            field: "handle",
            cellRenderer: "handle",
            hide: false,
            dispose: false,
          },
          {
            headerName: "结算账户",
            width: 120,
            field: "account",
            editable: true,
            cellEditor: "fat",
            cellEditorParams: {
              dispose: { key: "accountId", text: "account" },
            },
            hide: false,
            dispose: true,
          },
          {
            headerName: "结算金额",
            width: 120,
            field: "money",
            editable: true,
            hide: false,
            dispose: true,
          },
          {
            headerName: "结算号",
            width: 120,
            field: "settle",
            editable: true,
            hide: false,
            dispose: true,
          },
          {
            headerName: "备注信息",
            width: 120,
            field: "data",
            editable: true,
            hide: false,
            dispose: true,
          },
        ],
        status: [
          { text: "总条数", type: "count" },
          { text: "总金额", type: "sum", key: "money" },
        ],
      },
    };
  },
  computed: {
    //读取数据中心
    store() {
      return this.$store.state;
    },
  },
  created() {
    this.init();
  },
  methods: {
    //初始化
    init() {
      if (this.source == null) {
        this.form.time = this.$moment().format("YYYY-MM-DD");
        this.form.number = "SKD" + this.$moment().format("YYMMDDHHmmssS");
      } else {
        this.$axios.post("imy/get", { parm: this.source }).then((result) => {
          if (result.state == "success") {
            this.form = result.info.class;
            this.pushGrid(result.info.info, false);
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
      }
    },
    //保存单据
    save() {
      let form = this.$refs["form"];
      this.$lib
        .aloneValidate(form, ["customer", "time", "number"])
        .then(() => {
          let effect = this.handleGrid();
          if (effect == true) {
            //二次验证|兼容扩展验证
            form.validate((valid) => {
              if (valid) {
                this.$axios
                  .post("imy/save", {
                    class: this.form,
                    info: this.info,
                  })
                  .then((result) => {
                    if (result.state == "success") {
                      if (this.store.sys.fun.examine && this.form.id == 0) {
                        this.form.id = result.info;
                        this.$message({
                          type: "success",
                          message: "审核单据中...",
                        });
                        setTimeout(() => {
                          this.examine();
                        }, 996);
                      } else {
                        this.form.id = result.info;
                        this.$message({
                          type: "success",
                          message: "单据保存成功!",
                        });
                      }
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
              } else {
                this.$message({
                  type: "warning",
                  message: "表单验证错误,请检查并修正!",
                });
              }
            });
          } else {
            this.$message({ type: "warning", message: effect });
          }
        })
        .catch((message) => {
          this.$message({
            type: "warning",
            message: message,
          });
        });
    },
    //审核|反审核
    examine() {
      this.$axios
        .post("imy/examine", {
          parm: [this.form.id],
        })
        .then((result) => {
          if (result.state == "success") {
            this.form.examine = this.form.examine == 0 ? 1 : 0;
            this.$message({
              type: "success",
              message:
                (this.form.examine == 0 ? "反审核" : "审核") + "单据成功!",
            });
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
    //添加商品数据
    //scene:[true:常规场景|false:详情场景]
    pushGrid(items, scene = true) {
      let arr = [];
      for (let item of items) {
        let row = {
          uniqid: this.$lib.getUniqid(),
          accountId: scene ? null : item.account,
          account: scene ? "点击选择" : item.accountData.name,
          money: scene ? "" : item.money,
          settle: scene ? "" : item.settle,
          data: scene ? "" : item.data,
        };
        arr.push(row);
      }
      //场景判断
      if (scene) {
        this.grid.options.api.applyTransaction({ add: arr });
      } else {
        this.grid.options.api.setRowData(arr);
      }
      this.handleGrid();
    },
    //数据表格验证
    handleGrid() {
      let effect = true;
      let data = this.$grid.getGridData(this.grid.options);
      //数据处理
      for (var i = 0; i < data.length; i++) {
        if (data[i].accountId == null) {
          effect == true &&
            (effect = "数据表格第" + (i + 1) + "行结算账户不可为空!");
          continue;
        } else if (!this.$lib.synValidate("numerical", data[i].money)) {
          effect == true &&
            (effect = "数据表格第" + (i + 1) + "行结算金额不正确!");
          continue;
        } else {
        }
      }
      //场景判断
      if (effect == true) {
        //转存数据
        this.$set(
          this,
          "info",
          data.map((item) => {
            return {
              account: item.accountId,
              money: item.money,
              settle: item.settle,
              data: item.data,
            };
          })
        );
        //汇总信息
        this.summary();
      }
      return effect;
    },
    //汇总数据
    summary() {
      let total = this.$calc.chain(0);
      this.info.forEach((item) => {
        total.add(item.money);
      });
      this.form.total = total.done();
    },

    //切换页面
    switchPage(key) {
      this.$bus.emit("switchPage", key, true); //切换页面
    },
    //页面刷新
    reload() {
      this.$bus.emit("homeReload", this.$options.name);
      this.$message({
        type: "success",
        message: "页面刷新成功!",
      });
    },
  },
};
</script>
