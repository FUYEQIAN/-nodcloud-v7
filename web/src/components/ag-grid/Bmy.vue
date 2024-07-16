<template>
	<div class="bmy">
		<div class="title">
			<span>核销金额</span>
			<div class="auto" @click="auto">自动</div>
		</div>
	</div>
</template>
<script>
	//头组件|批量折扣率
	import Vue from 'vue';
    export default Vue.extend({
        name: 'Allowance',
        methods:{
			auto(){
				//基础数据 [+,-]
				let summary=[[0,0],[0,0]];
				let obj=[{imy:0,sell:1,sre:1,ice:1},{omy:0,buy:1,bre:1,oce:1},{sell:0,sre:0,ice:0,buy:1,bre:1,oce:1},{sre:0,sell:1},{bre:0,buy:1}][this.params.dispose.type()];
				let data = this.$grid.getGridData(this.params);
				for (var i = 0; i < data.length; i++) {
					if (data[i].key != null) {
						let mold=obj[data[i].moldType];
						let seat=data[i].total-0>0?0:1;
						summary[mold][seat]=this.$calc.chain(summary[mold][seat]).add(data[i].anwo).done();
					}
				}
				//计算核销 [+,-]
				let record=[
					[Math.min(summary[0][0],summary[1][0]),Math.max(summary[0][1],summary[1][1])],
					[Math.min(summary[0][0],summary[1][0]),Math.max(summary[0][1],summary[1][1])],
				];
				//填充数据
				for (var i = 0; i < data.length; i++) {
					if (data[i].key != null) {
						let mold=obj[data[i].moldType];
						let seat=data[i].total-0>0?0:1;
						let money=record[mold][seat];
						if(money==0){
							money='';
						}else{
							if((seat==0 && money>data[i].anwo)||(seat==1 && money<data[i].anwo)){
								money=data[i].anwo;
							}
							record[mold][seat]=this.$calc.chain(record[mold][seat]).subtract(money).done();
						}
						this.$grid.updateGridData(this.params,data[i].uniqid,'money',money);
					}
				}
				this.$message({
					type: "success",
					message: "已自动计算核销金额!"
				});
				this.params.context.runHandleGrid();
			}
        }
    })
</script>

<style scoped>
	.bmy{
		width: 100%;
	}
	.title{
		text-align: center;
	}
	.auto{
		display: initial;
		color: #fff;
		margin-left: 4px;
		padding: 0px 4px;
		border-radius: 2px;
		background: #9E9E9E;
	}
</style>