<template>
	<div class="lineFeed">
		<slot></slot>
	</div>
</template>
<script>
	export default {
		name: "NodEditor",
		props: {
			tag: {
				required: true
			},
			rule: {
				required: true
			}
		},
		methods: {
			init() {
				let dom = this.$el.querySelectorAll(this.tag);
				for (var i = this.rule; i < dom.length; i+=this.rule) {
					dom[i].parentNode.insertBefore(document.createElement("br"),dom[i]);
				}
			}
		},
		mounted() {
			this.init();
		}
	}
</script>