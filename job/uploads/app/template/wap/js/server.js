function myFunction(_this) {
    _this.value = _this.value.replace(/[^0-9]/g, '');
}

/* 选择不同服务操作：会员、增值服务、单项购买 */
$("ul#rating_select").on("click", "li", function() {

    var id			=	$(this).attr('data-id');

    var meal		=	$(this).attr('data-meal') ? $(this).attr('data-meal') : '';			//  1： 选择会员套餐服务，强制金额消费

    var price		=	$(this).attr('data-price');	        //	单项购买金额
    var server		=	$(this).attr('data-server') ? $(this).attr('data-server') : '';		//	单项服务类型
    var single_id	=	$(this).attr('data-singleid');	    //	单项购买金额

    var integral	=	$(this).attr('data-integral');		//	账户积分
    var	pro			=	$(this).attr('data-pro');			//	积分比例

    $(this).addClass("pitch_on"); 						    //	点击li添加class
    $(this).siblings('li').removeClass("pitch_on"); 	    //	删除兄弟li的class属性

    $(".vip_div").css('display', 'none');	                //	公用属性用来隐藏
    $("#rating_"+id).css('display', 'block');		        //	根据id显示点击的服务

    if (id<3){
        $('#vip_qy').css('display', 'block');
    }else{
        $('#vip_qy').css('display', 'none');
    }

    $(".vip_div").find('ul li .dredge_body_tab_body_right img').attr('src', imgWurl);	//	会员套餐默认都是未选中

    //	增值服务默认第一个展开,其他的未展开
    /*$(".detail_id").each(function(index,el){
        if(index > 0){
            $(el).css('display', 'none');
        }else{
            $(el).css('display', 'block');
        }
    })
    $(".vip_div").find('ul li i').attr('src', imgWurl);*/	    //	增值服务默认都是未选中



    if(parseFloat(price) > 0){		//	单项购买点击 获取服务金额

        $("#order_price").html(price);
        $("#server_price").val(price);
    }else{

        $("#order_price").html(0);
        $("#server_price").val(0);
    }

    if(server){						//	单项购买点击 获取服务类型
        $("#server").val(server);
        if(server == 'jobtop' || server == 'jobrec' || server == 'joburgent' || server == 'autojob' || server == 'partrec'){	//	职位推广服务
            if(parseFloat(price) > 0){

                $("#tg_price").val(price);
                $("#single_price").html(price);
                $("ul#job_tg").find('li').removeClass("wap_buy_packagescont_day_cur");
                $("ul#job_tg").find('.first').addClass("wap_buy_packagescont_day_cur");

            }
            $("#job_tg").css('display', 'block');
        }else{
            $("#job_tg").css('display', 'none');
        }

        $(".integral_reduce").css('display', 'block');
    }else{

        $("#server").val('');
        $(".integral_reduce").css('display', 'none');
    }

    if(single_id){

        $("#single_id").val(single_id);
    }else{

        $("#single_id").val('');
    }

    $("#single_integral").html(accMul(price, pro));

    if(integral && pro && only_price_arr.indexOf(server)==-1){	// 积分模式

        var integral_need	=	accMul(price, pro);

        $("#order_integral").html(accMul(price, pro));

        $("#xdays").val('');

        if(parseInt(integral) >= parseInt(integral_need)){	//	积分充足的情况

            $(".integral_buy_div").css('display', 'block');
            $(".integral_pay_div").css('display', 'none');

            $(".pay_style_div").css('display', 'none');

            $(".order_price_div").css('display', 'none');
            $(".order_integral_div").css('display', 'flex');

        }else{

            var integral_cj	=	accSub(parseInt(integral_need), parseInt(integral));	//	还需充值积分

            if(parseInt(integral_min) < parseInt(integral_cj)){

                $('#integral_int').val(parseInt(integral_cj));
                $('#order_price').html(accDiv(parseInt(integral_cj), pro));
            }else{

                $('#integral_int').val(parseInt(integral_min));
                $('#order_price').html(accDiv(parseInt(integral_min), pro));
            }

            $(".integral_buy_div").css('display', 'none');
            $(".integral_pay_div").css('display', 'block');

            $(".pay_style_div").css('display', 'block');

            $(".order_price_div").css('display', 'flex');
            $(".order_integral_div").css('display', 'none');

        }

    }else{

        $('#integral_dk').val('');

        $(".order_price_div").css('display', 'flex');

        if (server=='' && meal =='1' ){

            $(".integral_dk_div").css('display', 'none');
        }else if (server!='' && only_price_arr.indexOf(server)!=-1){

            $(".integral_dk_div").css('display', 'none');
        }else{

            $(".integral_dk_div").css('display', 'flex');
        }

        $(".order_integral_div").css('display', 'none');


        $(".pay_style_div").css('display', 'block');

    }
})

/* 选择不同会员等级操作 */
$("ul#vip_rating_1").on("click", "li", function() {

    var id			=	$(this).attr('data-id');		    //	会员等级ID
    var price		=	$(this).attr('data-price');		    //	会员服务金额
    var server		=	$(this).attr('data-server');	    //	服务类型

    var integral	=	$(this).attr('data-integral');	    //	账号积分
    var pro			=	$(this).attr('data-pro');		    //	积分比例

    var meal		=	$(this).attr('data-meal');		    //  1： 选择会员套餐服务，强制金额消费

    $(this).find('.dredge_body_tab_body_right img').attr('src', imgSurl);
    $(this).siblings('li').find('.dredge_body_tab_body_right img').attr('src', imgWurl);

    $("#server").val(server);
    $("#single_id").val(id);
    $("#integral_dk").val('');


    if(integral && pro){	// 积分模式

        var integral_need	=	accMul(parseFloat(price), parseInt(pro));

        if(parseInt(integral) >= parseInt(integral_need)){

            $("#order_integral").html(integral_need);
            $(".pay_style_div").css('display', 'none');
            $(".order_price_div").css('display', 'none');
            $(".order_integral_div").css('display', 'flex');

        }else{
            $(".pay_style_div").css('display', 'block');
            $(".order_integral_div").css('display', 'none');
            $(".order_price_div").css('display', 'flex');

            $(".integral_buy_div").css('display', 'none');
            $(".integral_pay_div").css('display', 'block');

            var	integral_need	=	 accMul(parseFloat(price), parseInt(pro));

            $("#single_integral").html(integral_need);

            var integral_cj		=	accSub(parseInt(integral_need), parseInt(integral));

            if(parseInt(integral_min) > parseInt(integral_cj)){

                $("#integral_int").val(integral_min);
                $("#order_price").html(accDiv(parseInt(integral_min), parseInt(pro)));
            }else{

                $("#integral_int").val(integral_cj);
                $("#order_price").html(accDiv(parseInt(integral_cj), parseInt(pro)));
            }
        }

    }else{

        $("#order_price").html(price);
        $("#server_price").val(price);

        $(".order_price_div").css('display', 'flex');
        $(".order_integral_div").css('display', 'none');

        if(meal == 1){
            $(".integral_dk_div").css('display', 'none');
        }else{
            $(".integral_dk_div").css('display', 'flex');
        }

        $(".pay_style_div").css('display', 'block');
    }
})

$("ul#vip_rating_2").on("click", "li", function() {

    var id			=	$(this).attr('data-id');		//	会员等级ID
    var price		=	$(this).attr('data-price');		//	会员服务金额
    var server		=	$(this).attr('data-server');	//	服务类型

    var integral	=	$(this).attr('data-integral');	//	账号积分
    var pro			=	$(this).attr('data-pro');		//	积分比例

    var meal		=	$(this).attr('data-meal');		//  1： 选择会员套餐服务，强制金额消费

    $(this).find('.dredge_body_tab_body_right img').attr('src', imgSurl);
    $(this).siblings('li').find('.dredge_body_tab_body_right img').attr('src', imgWurl);

    $("#server").val(server);
    $("#single_id").val(id);
    $("#integral_dk").val('');



    if(integral && pro){	// 积分模式

        var integral_need	=	accMul(parseFloat(price), parseInt(pro));

        if(parseInt(integral) >= parseInt(integral_need)){

            $("#order_integral").html(integral_need);

            $(".pay_style_div").css('display', 'none');

            $(".order_price_div").css('display', 'none');
            $(".order_integral_div").css('display', 'flex');

        }else{
            $(".pay_style_div").show();
            $(".order_integral_div").css('display', 'none');
            $(".order_price_div").show();

            $(".integral_buy_div").css('display', 'none');
            $(".integral_pay_div").css('display', 'block');

            var	integral_need	=	 accMul(parseFloat(price), parseInt(pro));

            $("#single_integral").html(integral_need);

            var integral_cj		=	accSub(parseInt(integral_need), parseInt(integral));

            if(parseInt(integral_min) > parseInt(integral_cj)){

                $("#integral_int").val(integral_min);
                $("#order_price").html(accDiv(parseInt(integral_min), parseInt(pro)));
            }else{

                $("#integral_int").val(integral_cj);
                $("#order_price").html(accDiv(parseInt(integral_cj), parseInt(pro)));
            }
        }

    }else{

        $("#order_price").html(price);
        $("#server_price").val(price);

        $(".order_price_div").css('display', 'flex');
        $(".order_integral_div").css('display', 'none');

        if(meal == 1){
            $(".integral_dk_div").css('display', 'none');
        }else{
            $(".integral_dk_div").css('display', 'flex');
        }

        $(".pay_style_div").css('display', 'block');
    }
})



/* 抵扣积分输入操作 */
function checkIntegralDK(integral, pro){

    var integral_dk		=	$("#integral_dk").val();		// 	抵扣输入积分
    var server_price	=	$("#server_price").val();		//	所选服务金额



    if(server_price == '' || parseFloat(server_price) == 0){

        $("#integral_dk").val('');
        showToast('请先选择购买服务！', 2);
        return false;
    }


    var order_price		=	server_price;

    var integral_need	=   accMul(parseFloat(server_price), parseInt(pro));	// 	套餐金额转积分

    if(parseInt(integral_need) == 0){

        integral_need	=	1;

    }else if(integral_need > parseInt(integral_need)){

        integral_need   = 	accAdd(parseInt(integral_need), 1);
    }

    if(integral_dk){

        if(parseInt(integral_dk) > 0){

            $("#integral_dk").val(parseInt(integral_dk));
        }

        if(parseInt(integral) >= parseInt(integral_need)) { 					// 	拥有积分充足

            if(parseInt(integral_dk) >= parseInt(integral_need)) { 				// 	输入抵扣积分超过购买积分

                $("#integral_dk").val(parseInt(integral_need));					//	抵扣积分变更最大所需积分

                order_price	=	0 ;	                                            // 	抵扣积分后所需金额
            } else {

                order_price	= 	accSub(parseFloat(server_price), accDiv(parseInt(integral_dk), parseInt(pro)));	//	抵扣积分后所需金额
            }
        } else {																//	拥有积分不充足

            if(parseInt(integral_dk) > parseInt(integral)) {					//	抵扣所有积分

                $("#integral_dk").val(parseInt(integral));

                order_price	= 	accSub(parseFloat(server_price), accDiv(integral, parseInt(pro)));		//	抵扣积分后所需金额
            } else {

                order_price	= 	accSub(parseFloat(server_price), accDiv(integral_dk, parseInt(pro)));	//	抵扣积分后所需金额
            }
        }
    }

    /* 根据抵扣后金额，判断支付方式 */
    if(order_price > 0){

        $(".order_price_div").css('display', 'flex');
        $(".order_integral_div").css('display', 'none');
        $(".pay_style_div").css('display', 'block');
        $("#order_price").html(order_price);
    }else{

        $(".order_integral_div").css('display', 'flex');
        $(".order_price_div").css('display', 'none');
        $(".pay_style_div").css('display', 'none');
        $("#order_integral").html(parseInt(integral_need));
    }
}

/* 支付方式选择  */
function paycheck(type){

    $("#paytype").val(type);

    $(".dredge_body_wx_icon img").attr('src', imgWurl);

    if(type == 'alipay'){

        $(".dredge_body_zfb .dredge_body_wx_icon img").attr('src', imgSurl);
    }
}

/* 金额支付购买 */
function orderBuy(){

    var server 		= $("#server").val();
    var paytype 	= $("#paytype").val();
    var price       = $('#order_price').html();
    var single_id 	= 	$("#single_id").val();
    if (price == 0 && single_id != ''){

        integralBuy();
    }else {

        if (server == '') {

            showToast('请选择购买服务！');
            return false;
        }

        if (paytype == '') {

            showToast('请选择支付方式！');
            return false;
        }

        var integral_dk = $("#integral_dk").val();

        var integral_int = $("#integral_int").val();



        var ajaxUrl = wapurl+'/member/index.php?c=getOrder';

        var pData = {
            paytype: paytype,
            server: server,
            price_int: integral_int,
         
            dkjf: integral_dk
        };

        if (server == 'issuejob' || server == 'invite' || server == 'createson') {


        } else if (server == 'sxjob') {

            pData.sxjobid = single_id;

        } else if (server == 'jobtop') {

            var days = $("#days").val();
            pData.days = days;
            pData.zdjobid = single_id;

        } else if (server == 'jobrec') {

            var days = $("#days").val();
            pData.days = days;
            pData.recjobid = single_id;

        } else if (server == 'joburgent') {

            var days = $("#days").val();
            pData.days = days;
            pData.ujobid = single_id;

        } else if (server == 'sxpart') {

            pData.sxpartid = single_id;

        } else if (server == 'partrec') {

            var days = $("#days").val();
            pData.days = days;
            pData.recpartid = single_id;

        } else if (server == 'downresume') {

            pData.eid = single_id;

        } else if (server == 'zph') {
            var bid = $('#bid').val();
            pData.zid = single_id;
            pData.bid = bid;

        } else if (server == 'vip') {

            pData.ratingid = single_id;

        } else if (server == 'pack') {

            pData.tcid = single_id;

        } else if (server == 'autojob') {

            var days = $("#days").val();
            pData.days = days;
            pData.jobautoids = single_id;

        }
        $.post(ajaxUrl, pData, function (data) {

            var data = eval('(' + data + ')');

            if (data.error == '0') { // 下单成功

                showToast(data.msg, 2, function () {
                    location.href = data.url;
                });
                return false;
            } else {

                showToast(data.msg, 2, function () {
                    location.reload(true);
                });
                return false;
            }
        });
    }
}

/* 积分支付购买 */
function integralBuy(){

    showLoading();

    var server 		= 	$("#server").val();
    var single_id	= 	$("#single_id").val();


    var pData = {server: server};
    if(server == 'issuejob'){

        var rurl  	= 	wapurl + 'member/index.php?c=jobcolumn';

    }else if(server == 'jobtop'){

        var days	=	$("#days").val();
        var rurl  	= 	wapurl + 'member/index.php?c=job';
        pData.zdjobid = single_id;
        pData.days = days;

    }else if(server == 'jobrec'){

        var days	=	$("#days").val();
        var rurl  	= 	wapurl + 'member/index.php?c=job';
        pData.recjobid = single_id;
        pData.days = days;

    }else if(server == 'joburgent'){

        var days	=	$("#days").val();
        var rurl  	= 	wapurl + 'member/index.php?c=job';
        pData.ujobid = single_id;
        pData.days = days;

    }else if(server == 'sxjob'){

        var rurl  	= 	wapurl + 'member/index.php?c=job';
        pData.sxjobid = single_id;

    }else if(server == 'sxpart'){

        var rurl  	= 	wapurl + 'member/index.php?c=part';
        pData.sxpartid = single_id;

    }else if(server == 'partrec'){

        var days	=	$("#days").val();
        var rurl  	= 	wapurl + 'member/index.php?c=part';
        pData.recpartid = single_id;
        pData.days = days;

    }else if(server == 'downresume'){

        var rurl  	= 	wapurl + 'index.php?c=resume&a=show&id=' + single_id + '&down=1';
        pData.eid = single_id;

    }else if(server == 'invite'){

        var rurl  	=	wapurl + 'index.php?c=resume&a=invite&uid=' + single_id + '&invite=1';

    }else if(server == 'zph'){

        var bid   	= 	$("#bid").val();
        var rurl  	=	wapurl + 'index.php?c=zph&a=reserve&id=' + single_id + '&zph=1';
        pData.zid = single_id;
        pData.bid = bid;
    }else if(server == 'vip'){

        var rurl  	=	wapurl + 'member/index.php?c=com';
        pData.ratingid = single_id;
    }else if(server == 'pack'){

        var rurl  	=	wapurl + 'member/index.php?c=com';
        pData.tcid = single_id;
    }else if(server == 'autojob'){

        var days	=	$("#days").val();
        var rurl  	= 	wapurl + 'member/index.php?c=job';
        pData.jobautoids = single_id;
        pData.days = days;

    }else if(server == 'createson'){

        var rurl  	= 	wapurl + 'member/index.php?c=child';
    }
    var ajaxUrl     =   wapurl+'/member/index.php?c=dkzf';
    $.post(ajaxUrl, pData, function(data){

        hideLoading();

        var data = eval('(' + data + ')');

        if(data.error == '0') { // 成功

            showToast(data.msg, 2, function() {
                location.href = rurl;
            });

            return false;
        } else {

            showToast(data.msg, 2, function() {
                location.reload(true);
            });

            return false;
        }
    });
}

