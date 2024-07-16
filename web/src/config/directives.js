import Vue from 'vue';
// Element弹层拖动
Vue.directive('madeDialog', {
	bind(el){
		const dialogHeaderEl = el.querySelector('.el-dialog__header')
		const dragDom = el.querySelector('.el-dialog')
		dialogHeaderEl.style.cursor = 'move'
		const sty = dragDom.currentStyle || window.getComputedStyle(dragDom, null)
		dialogHeaderEl.onmousedown = (e) => {
			const disX = e.clientX - dialogHeaderEl.offsetLeft
			const disY = e.clientY - dialogHeaderEl.offsetTop
			let styL, styT
			if (sty.left.includes('%')) {
				styL = +document.body.clientWidth * (+sty.left.replace(/\%/g, '') / 100)
				styT = +document.body.clientHeight * (+sty.top.replace(/\%/g, '') / 100)
			} else {
				styL = +sty.left.replace(/\px/g, '')
				styT = +sty.top.replace(/\px/g, '')
			}
			document.onmousemove = function(e) {
				const l = e.clientX - disX
				const t = e.clientY - disY
				dragDom.style.left = `${l + styL}px`
				dragDom.style.top = `${t + styT}px`
			}
			document.onmouseup = function(e) {
				document.onmousemove = null
				document.onmouseup = null
			}
		}
	}
});
//修复浮动层不可拖动[ELEMENT-BUG]
Vue.directive('madeTable', {
	update(el,bind,vnode,oldNode){
		if(el.getAttribute('render')==null && vnode.componentInstance.data.length>0){
			el.setAttribute('render','finish');
			setTimeout(()=>{
				vnode.componentInstance.layout.updateElsHeight();
			},513);
		}
	}
})
//编辑模块失去焦点停止编辑[AgGrid]
Vue.directive('madeAgGrid', {
	inserted(el,bind,vnode){
		vnode.context.$el.onclick=(e)=>{
			let dom=el.querySelector('.ag-cell-inline-editing');
			if(dom){
				if(!dom.contains(e.target) && !e.target.classList.contains('agGridPopup')){
					vnode.componentInstance.gridOptions.api.stopEditing();
				}
			}
		};
	}
})