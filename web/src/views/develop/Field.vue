<template>
  <div class="field area">
    <div class="layout">
      <el-popover
        ref="searchPopover"
        popper-class="searchPopover"
        placement="bottom-start"
      >
        <el-form class="searchFrom" ref="searchFrom" inline>
          <el-form-item>
            <el-input
              placeholder="请输入模块名称"
              v-model="searchFrom.name"
              clearable
            ></el-input>
          </el-form-item>
          <el-form-item>
            <el-input
              placeholder="请输入模块标识"
              v-model="searchFrom.key"
              clearable
            ></el-input>
          </el-form-item>
          <el-divider></el-divider>
          <el-button
            class="searchBtn"
            icon="el-icon-search"
            @click="record(1)"
          ></el-button>
        </el-form>
        <el-button slot="reference" icon="el-icon-more"></el-button>
      </el-popover>
      <el-button-group>
        <el-button
          v-if="tableSelection.length != 0"
          @click="del(tableSelection)"
          type="info"
          >删除</el-button
        >
        <el-button @click="set(0)">新增</el-button>
        <el-button @click="reload">刷新</el-button>
      </el-button-group>
    </div>
    <el-divider></el-divider>
    <el-table
      :data="tableData"
      height="calc(100% - 90px)"
      @selection-change="selectionChange"
      border
      v-madeTable
    >
      <el-table-column
        type="selection"
        align="center"
        width="39px"
        fixed="left"
      ></el-table-column>
      <el-table-column
        prop="name"
        label="模块名称"
        align="center"
        width="160px"
      ></el-table-column>
      <el-table-column
        prop="extension.key"
        label="模块标识"
        align="center"
        width="160px"
      ></el-table-column>
      <el-table-column
        prop="data"
        label="备注信息"
        align="center"
        width="200px"
      ></el-table-column>
      <el-table-column prop="set" label="相关操作" align="center" width="160px">
        <template slot-scope="scope">
          <el-button-group>
            <el-button @click="set(scope.row.id)" size="mini">详情</el-button>
            <el-button @click="del([scope.row.id])" size="mini">删除</el-button>
          </el-button-group>
        </template>
      </el-table-column>
    </el-table>
    <el-pagination
      class="tablePagination"
      :current-page.sync="page.current"
      :total="page.total"
      :page-size.sync="page.size"
      :page-sizes="page.sizes"
      :pager-count="page.count"
      @size-change="record(1)"
      @current-change="record(0)"
      layout="prev,pager,next,jumper,sizes,total"
    >
    </el-pagination>
    <el-dialog :visible.sync="dialog" title="详情" :fullscreen="true">
      <transition name="el-fade-in">
        <template v-if="dialog">
          <el-row :gutter="12">
            <el-col :md="6">
              <el-card>
                <div slot="header">
                  <span>模块信息</span>
                </div>
                <div class="cardMain">
                  <el-form
                    :model="form"
                    :rules="rules"
                    ref="form"
                    label-width="80px"
                  >
                    <el-form-item label="模块名称" prop="name">
                      <el-input
                        placeholder="请输入模块名称"
                        v-model="form.name"
                      ></el-input>
                    </el-form-item>
                    <el-form-item label="模块标识" prop="key">
                      <el-select v-model="form.key" placeholder="模块标识">
                        <el-option label="用户管理" value="user"></el-option>
                        <el-option label="人员管理" value="people"></el-option>
                        <el-option
                          label="客户管理"
                          value="customer"
                        ></el-option>
                        <el-option
                          label="供应商管理"
                          value="supplier"
                        ></el-option>
                        <el-option label="商品管理" value="goods"></el-option>
                        <el-option label="采购订单" value="bor"></el-option>
                        <el-option label="采购单" value="buy"></el-option>
                        <el-option label="采购退货单" value="bre"></el-option>
                        <el-option label="销售订单" value="sor"></el-option>
                        <el-option label="销售单" value="sell"></el-option>
                        <el-option label="销售退货单" value="sre"></el-option>
                        <el-option label="调拨单" value="swap"></el-option>
                        <el-option label="其它入库单" value="entry"></el-option>
                        <el-option label="其它出库单" value="extry"></el-option>
                        <el-option label="收款单" value="imy"></el-option>
                        <el-option label="付款单" value="omy"></el-option>
                        <el-option label="核销单" value="bill"></el-option>
                        <el-option label="转账单" value="allot"></el-option>
                        <el-option label="其它收入单" value="ice"></el-option>
                        <el-option label="其它支出单" value="oce"></el-option>
                      </el-select>
                    </el-form-item>
                    <el-form-item label="备注信息">
                      <el-input
                        placeholder="请输入备注信息"
                        v-model="form.data"
                      ></el-input>
                    </el-form-item>
                  </el-form>
                  <el-divider content-position="left">字段类型</el-divider>
                  <el-tag @click="addField('text')">普通文本</el-tag>
                  <el-tag @click="addField('time')">时间文本</el-tag>
                  <el-tag @click="addField('textarea')">多行文本</el-tag>
                  <el-tag @click="addField('select')">单选菜单</el-tag>
                  <el-tag @click="addField('checkbox')">多选菜单</el-tag>
                  <el-tag @click="addField('upload')">附件数据</el-tag>
                </div>
              </el-card>
            </el-col>
            <el-col :md="10">
              <el-card class="fieldList">
                <div slot="header">
                  <span>字段列表</span>
                </div>
                <div class="cardMain fieldMain">
                  <el-form
                    :model="more"
                    label-width="80px"
                    ref="diyFrom"
                    disabled
                  >
                    <FieldForm
                      v-model="more"
                      :rule="form.fields"
                      :drag="true"
                      @dragChoose="itemChoice"
                      @dragEnd="itemChoice"
                    ></FieldForm>
                  </el-form>
                </div>
              </el-card>
            </el-col>
            <el-col :md="8">
              <el-card>
                <div slot="header">
                  <span>字段属性</span>
                </div>
                <div class="cardMain">
                  <p v-if="field.index == null" class="fieldTip">
                    Yo.选择字段后即可显示配置信息
                  </p>
                  <el-form v-else label-width="80px">
                    <el-form-item label="标题名称">
                      <el-input
                        placeholder="请输入标题名称"
                        v-model="field.source.label"
                        @input="validateChange"
                      ></el-input>
                    </el-form-item>
                    <el-form-item label="数据标识">
                      <el-input
                        placeholder="请输入数据标识"
                        :value="field.source.model"
                        @input="modelValue"
                      ></el-input>
                    </el-form-item>
                    <el-form-item
                      v-if="
                        ['text', 'time', 'textarea'].indexOf(
                          field.source.type
                        ) != -1
                      "
                      label="默认内容"
                    >
                      <el-input
                        placeholder="请输入默认内容"
                        v-model="field.source.value"
                        @input="inputChange"
                      ></el-input>
                    </el-form-item>
                    <el-form-item
                      v-if="field.source.type == 'select'"
                      label="选项数据"
                      class="chooseGroup"
                    >
                      <i
                        class="el-icon-circle-plus-outline"
                        @click="selectAdd"
                        style="width: 100%"
                      >
                        新增选项</i
                      >
                      <el-radio-group v-model="field.source.value">
                        <template
                          v-for="(option, index) in field.source.options"
                        >
                          <div class="choose">
                            <el-radio
                              :label="option"
                              @click.native.prevent="selectChoice(option)"
                              ><b></b
                            ></el-radio>
                            <el-input
                              placeholder="请输入选项名称"
                              :value="option"
                              @input="(val) => selectValue(val, index)"
                              size="mini"
                              @keydown.native.stop
                            >
                              <template slot="append">
                                <i
                                  class="el-icon-remove-outline"
                                  @click="selectDel(index)"
                                ></i>
                              </template>
                            </el-input>
                          </div>
                        </template>
                      </el-radio-group>
                    </el-form-item>
                    <el-form-item
                      v-if="field.source.type == 'checkbox'"
                      label="选项数据"
                      class="chooseGroup"
                    >
                      <i
                        class="el-icon-circle-plus-outline"
                        @click="checkboxAdd"
                        style="width: 100%"
                      >
                        新增选项</i
                      >
                      <el-checkbox-group
                        v-model="field.source.value"
                        @change="checkboxChange"
                      >
                        <template
                          v-for="(checkbox, index) in field.source.checkboxs"
                        >
                          <div class="choose">
                            <el-checkbox :label="checkbox"><b></b></el-checkbox>
                            <el-input
                              placeholder="请输入选项名称"
                              :value="checkbox"
                              @input="(val) => checkboxValue(val, index)"
                              size="mini"
                            >
                              <template slot="append">
                                <i
                                  class="el-icon-remove-outline"
                                  @click="checkboxDel(index)"
                                ></i>
                              </template>
                            </el-input>
                          </div>
                        </template>
                      </el-checkbox-group>
                    </el-form-item>
                    <el-form-item
                      v-if="
                        ['text', 'time', 'textarea', 'select'].indexOf(
                          field.source.type
                        ) != -1
                      "
                      label="提示信息"
                    >
                      <el-input
                        placeholder="请输入提示信息"
                        v-model="field.source.placeholder"
                      ></el-input>
                    </el-form-item>
                    <el-form-item label="是否必填">
                      <el-checkbox
                        @change="validateChange"
                        v-model="field.source.validate"
                      ></el-checkbox>
                    </el-form-item>
                    <el-button @click="delField" icon="el-icon-delete"
                      >删除</el-button
                    >
                  </el-form>
                </div>
              </el-card>
            </el-col>
          </el-row>
        </template>
      </transition>
      <span slot="footer" class="dialog-footer">
        <el-button @click="dialog = false">取消</el-button>
        <el-button @click="save" type="primary">保存</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
import FieldForm from "@/components/lib/FieldForm";
export default {
  name: "Field",
  components: {
    FieldForm,
  },
  data() {
    return {
      searchFrom: {
        name: "",
        key: "",
      },
      tableData: [],
      tableSelection: [],
      page: {
        current: 1,
        total: 0,
        size: 30,
        sizes: [30, 60, 90, 150, 300],
        count: 5,
      },
      dialog: false,
      form: {
        id: 0,
        name: "",
        key: "",
        data: "",
        fields: [],
      },
      rules: {
        name: {
          required: true,
          message: "请输入模块名称",
          trigger: "blur",
        },
        key: {
          required: true,
          message: "请输入模块标识",
          trigger: "blur",
        },
      },
      field: {
        index: null,
        source: {},
      },
      more: {},
    };
  },
  created() {
    this.record(1); //获取数据
  },
  methods: {
    //获取数据
    record(page) {
      page == 0 || (this.page.current = page);
      let parm = Object.assign(
        {
          page: this.page.current,
          limit: this.page.size,
        },
        this.searchFrom
      );
      this.$axios.post("field/record", parm).then((result) => {
        if (result.state == "success") {
          this.page.total = result.count;
          this.tableData = result.info;
          this.$refs["searchPopover"].showPopper = false;
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
    //设置数据
    set(id) {
      //1.初始化数据
      this.form = this.$lib.extend(true, {}, this.$options.data().form);
      this.field = { index: null, source: {} };
      this.more = {};
      //2.请求数据
      if (id > 0) {
        this.$axios
          .post("field/get", {
            id: id,
          })
          .then((result) => {
            if (result.state == "success") {
              this.form = result.info;
              this.dialog = true; //显示弹层
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
        this.dialog = true; //显示弹层
      }
    },
    //保存数据
    save() {
      this.$refs["form"].validate((valid) => {
        if (valid) {
          this.$axios.post("field/save", this.form).then((result) => {
            if (result.state == "success") {
              this.record(0);
              this.dialog = false;
              this.$lib.updateStore(this);
              this.$message({
                type: "success",
                message: "详情保存成功!",
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
        }
      });
    },
    //删除数据
    del(parm) {
      this.$confirm("您确定要删除选中数据吗?", "提示", {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning",
      })
        .then(() => {
          this.$axios.post("field/del", { parm: parm }).then((result) => {
            if (result.state == "success") {
              this.record(0);
              this.$message({
                type: "success",
                message: "删除成功!",
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
        })
        .catch(() => {});
    },
    //表格选中数据改变
    selectionChange(parm) {
      let data = [];
      for (let parmVo of parm) {
        data.push(parmVo.id);
      }
      this.tableSelection = data;
    },
    //增加表单配置
    addField(type) {
      let obj = {};
      let variable = 1;
      while (this.more.hasOwnProperty("key" + variable)) {
        variable++;
      }
      let model = "key" + variable;
      if (type == "text") {
        obj = {
          type: "text",
          model: model,
          label: "普通文本",
          value: "",
          placeholder: "",
          prop: model,
          validate: false,
          rules: [],
        };
      } else if (type == "time") {
        obj = {
          type: "time",
          model: model,
          label: "时间文本",
          value: "",
          placeholder: "",
          prop: model,
          validate: false,
          rules: [],
        };
      } else if (type == "textarea") {
        obj = {
          type: "textarea",
          model: model,
          label: "多行文本",
          value: "",
          placeholder: "",
          prop: model,
          validate: false,
          rules: [],
        };
      } else if (type == "select") {
        obj = {
          type: "select",
          model: model,
          label: "单选菜单",
          value: "",
          placeholder: "",
          options: ["选项1", "选项2"],
          prop: model,
          validate: false,
          rules: [],
        };
      } else if (type == "checkbox") {
        obj = {
          type: "checkbox",
          model: model,
          label: "多选菜单",
          value: [],
          checkboxs: ["选项1", "选项2"],
          prop: model,
          validate: false,
          rules: [],
        };
      } else if (type == "upload") {
        obj = {
          type: "upload",
          model: model,
          label: "附件数据",
          value: [],
          prop: model,
          validate: false,
          rules: [],
        };
      }
      this.$set(this.more, obj.model, obj.value); //数据源
      this.form.fields.push(obj); //字段配置
      this.$nextTick(() => {
        this.itemChoice(this.form.fields.length - 1);
      });
    },
    //删除字段
    delField() {
      //删除数据源
      delete this.more[this.field.source.model];
      //删除配置
      this.form.fields.splice(this.field.index, 1);
      this.field = { index: null, source: {} };
      //初始选中
      document.querySelectorAll(".fieldList .el-form-item").forEach((dom) => {
        dom.classList.remove("isItem");
      });
    },
    //设置字段选择
    itemChoice(index) {
      this.field.index = index;
      this.field.source = this.$lib.extend(true, {}, this.form.fields[index]);
      let allDom = document.querySelectorAll(".fieldList .el-form-item");
      allDom.forEach((dom) => {
        dom.classList.remove("isItem");
      });
      allDom[index].classList.add("isItem");
    },
    //数据标识内容改变
    modelValue(value) {
      if (this.$lib.validate("variable", value)) {
        let index = this.form.fields.findIndex((item) => item.model == value);
        if (index == -1) {
          //更新数据源
          delete this.more[this.field.source.model];
          this.$set(this.more, value, this.field.source.value);
          //更新字段配置
          this.field.source.model = value;
          this.field.source.prop = value;
          this.$set(this.form.fields, this.field.index, this.field.source);
        } else {
          this.$message({
            type: "warning",
            message: "数据标识重复,修改已撤销!",
          });
        }
      } else {
        this.$message({
          type: "warning",
          message: "数据标识错误,修改已撤销!",
        });
      }
    },
    //设置字段配置验证规则
    validateChange() {
      let source = this.field.source;
      if (source.validate) {
        if (["text", "time", "textarea"].indexOf(source.type) != -1) {
          source.rules = {
            required: true,
            message: "请输入" + source.label,
            trigger: "blur",
          };
        } else if (["select", "checkbox"].indexOf(source.type) != -1) {
          source.rules = {
            required: true,
            message: "请选择" + source.label,
            trigger: "change",
          };
        } else if (source.type == "upload") {
          source.rules = {
            required: true,
            message: "请上传" + source.label,
          };
        } else {
          source.rules = [];
        }
      } else {
        source.rules = [];
      }
      this.$set(this.form.fields, this.field.index, source);
    },
    //字段属性|通用默认值内容修改
    inputChange() {
      this.more[this.field.source.model] = this.field.source.value;
      this.$set(this.form.fields, this.field.index, this.field.source);
    },
    //字段属性|单选菜单增加内容
    selectAdd() {
      let variable = 1;
      while (this.field.source.options.indexOf("选项" + variable) != -1) {
        variable++;
      }
      this.field.source.options.push("选项" + variable);
      this.$set(this.form.fields, this.field.index, this.field.source);
    },
    //字段属性|单选菜单删除内容
    selectDel(index) {
      let source = this.field.source;
      if (source.options[index] == source.value) {
        source.value = "";
        this.more[source.model] = "";
      }
      source.options.splice(index, 1);
      this.$set(this.form.fields, this.field.index, source);
    },
    //字段属性|单选菜单内容选择
    selectChoice(value) {
      let source = this.field.source;
      source.value = source.value == value ? "" : value;
      this.more[source.model] = source.value;
      this.$set(this.form.fields, this.field.index, source);
    },
    //字段属性|单选菜单内容改变
    selectValue(value, index) {
      let source = this.field.source;
      if (source.options.indexOf(value) == -1) {
        if (source.value == source.options[index]) {
          source.value = value;
          this.more[source.model] = value;
        }
        this.$set(source.options, index, value);
        this.$set(this.form.fields, this.field.index, source);
      } else {
        this.$message({
          type: "warning",
          message: "选项内容重复,修改已撤销!",
        });
      }
    },
    //字段属性|多选菜单增加内容
    checkboxAdd() {
      let variable = 1;
      while (this.field.source.checkboxs.indexOf("选项" + variable) != -1) {
        variable++;
      }
      this.field.source.checkboxs.push("选项" + variable);
      this.$set(this.form.fields, this.field.index, this.field.source);
    },
    //字段属性|多选菜单删除内容
    checkboxDel(index) {
      let source = this.field.source;
      let key = source.value.indexOf(source.checkboxs[index]);
      if (key != -1) {
        source.value.splice(key, 1);
        this.more[source.model] = source.value;
      }
      source.checkboxs.splice(index, 1);
      this.$set(this.form.fields, this.field.index, source);
    },
    //字段属性|多选菜单内容改变
    checkboxChange(value) {
      let source = this.field.source;
      this.more[source.model] = value;
      this.$set(this.form.fields, this.field.index, source);
    },
    //字段属性|多选菜单内容改变
    checkboxValue(value, index) {
      let source = this.field.source;
      if (source.checkboxs.indexOf(value) == -1) {
        let key = source.value.indexOf(source.checkboxs[index]);
        if (key != -1) {
          this.$set(source.value, key, value);
          this.more[source.model] = source.value;
        }
        this.$set(source.checkboxs, index, value);
        this.$set(this.form.fields, this.field.index, source);
      } else {
        this.$message({
          type: "warning",
          message: "选项内容重复,修改已撤销!",
        });
      }
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
<style scoped>
.layout {
  display: flex;
  justify-content: space-between;
}
.el-card {
  min-height: calc(100vh - 160px);
}
.el-tag {
  width: 100%;
  text-align: center;
  margin-bottom: 12px;
}
.fieldForm {
  max-height: calc(100vh - 256px);
  overflow: auto;
}
.fieldList >>> .el-form-item {
  padding: 10px 6px;
  margin-bottom: 0px;
}
.fieldList >>> .isItem {
  background: #f7f8fa;
  border-left: 2px solid #46cdcf;
}
.fieldTip {
  text-align: center;
}
.chooseGroup .choose {
  margin-bottom: 6px;
  line-height: 0;
}
.chooseGroup .choose .el-radio {
  margin-right: 0;
  margin-top: 6px;
}
.chooseGroup .choose .el-checkbox {
  margin-top: 6px;
}
.chooseGroup .choose .el-input {
  width: 150px;
}
.chooseGroup .choose >>> .el-input-group__append {
  padding: 0 12px;
}
</style>
