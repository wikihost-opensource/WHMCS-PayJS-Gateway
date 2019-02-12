<!--
    可用变量

    $url  - 微信支付二维码展示url
    $wechatpay_uri - 拉起微信支付手机端 url

-->
<script src="/assets/js/jquery.min.js"></script>
<script src="/assets/js/bootstrap.min.js"></script>
<div class="wechat">
    <div id="wechatimg">
        <img src="{$url}">
    </div>
    <a href="{$wechatpay_uri}" target="_blank" id="wepayDiv" class="btn btn-success" style="width: auto; ">使用手机微信扫描上面二维码进行支付</a>
</div>
<script>
jQuery(document).ready(function() {
	var paid_status = false
	var paid_timer = setInterval(function(){
		$.ajax({
			type: "get",
			url : window.location.href.replace('viewinvoice.php', 'modules/gateways/class/payjs/invoice.php'),
			dataType : "json",
			success: function(data){
				if (data.data){
                    if (data.data.paid){
                        clearInterval(paid_timer)
                        $('#paidsuccess').modal('show')
                        setTimeout(function(){location.reload()},3000)
                    }
                }
			}})
	},1500)
			
})
</script>
<div class="modal fade" id="paidsuccess">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title"><p class="text-success">支付成功</p></h4>
      </div>
      <div class="modal-body">
        <p>本页面将在3秒后刷新</p>
      </div>
    </div>
  </div>
</div>