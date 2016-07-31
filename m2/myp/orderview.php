<?php
	include dirname(__FILE__) . "/../_header.php";
	@include $shopRootDir . "/lib/page.class.php";

	//chkMemberMobile();
	if (!$sess && !$_COOKIE[guest_ordno]) go("../mem/login.php?returnUrl=$_SERVER[PHP_SELF]");

	if(!is_object($order)){
		$order = Core::loader('order');
		$order->load($_GET['ordno']);
	}
	if(!is_object($mypage_paymentDetails)) $mypage_paymentDetails = Core::loader('mypage_paymentDetails', $_GET['ordno']);
	if(!is_object($cashreceipt)) $cashreceipt = new cashreceipt();
	$prnSettleEtcMsg = '';

	@include $shopRootDir . "/conf/pg.cashbag.php";
	$r_exc = $r_kind = array();
	$cashbagprice = 0;
	if($cashbag['paykind'])$r_kind = unserialize($cashbag['paykind']);
	if($cashbag['e_refer'])$r_exc = unserialize($cashbag['e_refer']);

	$query = "
	select * from
		".GD_ORDER." a
		left join ".GD_LIST_BANK." b on a.bankAccount=b.sno
		left join ".GD_LIST_DELIVERY." c on a.deliveryno=c.deliveryno
	where a.ordno = '$_GET[ordno]'
	";
	$data = $db->fetch($query,1);
	$prnSettlePrice = $mypage_paymentDetails->getRealPrnSettlePrice(); //�����ݾ� - ��ҿϷ�� �����ݾ�

	if (!$data) msg("�ش� �ֹ��� �������� �ʽ��ϴ�",-1);

	### ���� üũ
	if ($sess[m_no]){
		if ($data[m_no]!=$sess[m_no]) msg("���ٱ����� �����ϴ�",-1);
	} else {
		if ($data[nameOrder]!=$_COOKIE[guest_nameOrder] || $data[m_no]) msg("���ٱ����� �����ϴ�",-1);
	}

	$query = "
	select count(*) from
		".GD_ORDER_ITEM."
	where
		ordno = '$_GET[ordno]' and istep < 40
		";
	list($icnt) = $db -> fetch($query);

	$query = "
	select b.*, a.* from
		".GD_ORDER_ITEM." a
		left join ".GD_GOODS." b on a.goodsno=b.goodsno
	where
		a.ordno = '$_GET[ordno]'
	";
	$res = $db->query($query);
	while ($sub=$db->fetch($res)){
		$item[] = $sub;

		if(substr($sub[coupon_emoney],-1) == '%') $sub[coupon_emoney] = getDcprice($sub[price],$sub[coupon_emoney]);

		if($icnt == 0){ //��� �ֹ���ǰ�� ���,ȯ���� ���
			$coupon_emoney += $sub[coupon_emoney] * $sub[ea];

		}else if ($sub[istep]<40){
			$coupon_emoney += $sub[coupon_emoney] * $sub[ea];

			if( in_array($sub['goodsno'],$r_exc) ) $minus += $sub[price] * $sub[ea];
			$cashbagprice += $sub[price] * $sub[ea];
		}
	}
	$cashbagprice -= $minus;

	@include $shopRootDir . "/conf/config.pay.php";
	@include $shopRootDir . "/conf/pg.$cfg[settlePg].php";
	@include $shopRootDir . "/conf/pg.escrow.php";
	@include $shopRootDir . "/conf/egg.usafe.php";

	if($set['delivery']['deliverynm'] == '')$set['delivery']['deliverynm'] = '�⺻���';

	if($data[step2] == '50' || $data[step2] == '54'){

		$r_deli = explode('|',$set['r_delivery']['title']);

	}

	### ���ݿ�����
	$cashReceipt = array();
	$cashReceipt = $cashreceipt->getCashReceiptCalCulate($_GET['ordno']);
	$r_type = array(
			"a"	=> "NBANK",
			"o"	=> "ABANK",
			"v"	=> "VBANK",
			);
	$cashReceipt['type'] = $r_type[$data['settlekind']];

	if($data['settleInflow'] == 'payco'){
		$pg['receipt'] = 'N';
	}
	else {
		if ($data['cashreceipt_ectway'] == 'Y')
		{
			$data['cashreceipt'] = '-';
			$tpl->define('cash_receipt',"proc/_cashreceipt.htm");
		}
		else if ($set['receipt']['publisher'] != 'seller'){
			$tpl->define('cash_receipt',"order/cash_receipt/{$cfg[settlePg]}.htm");
		}
		else if ($set['receipt']['publisher'] == 'seller'){
			if (is_object($cashreceipt))
			{
				$cashreceipt->prnUserReceipt($_GET['ordno']);
				$tpl->assign('receipt',$cashreceipt);
			}
			$tpl->define('cash_receipt',"proc/_cashreceipt.htm");
		}
	}

	### ���ݰ�꼭
	if ( $set[tax][useyn] == 'y' ){
		$tmp = 0;
		if ( $set[tax][ "use_{$data[settlekind]}" ] == 'on' ) $tmp++;
		if ( in_array($set[tax][step], array('1', '2', '3', '4')) && $data[step] >= $set[tax][step] && !$data[step2] ) $tmp++;
		list($cnt) = $db->fetch("select count(*) from ".GD_ORDER_ITEM." where ordno='$_GET[ordno]' and tax='1'");
		if ( $cnt >= 1 ) $tmp++;
		if ( $tmp == 3 ) $data[taxapp] = 'Y';
		if (is_object($cashreceipt) && $cashreceipt->writeable != 'true' && $set['receipt']['publisher'] == 'seller') $data['taxapp'] = 'N'; // ���ݿ����� �������̸� ���ݰ�꼭 ��û�Ұ�
	}

	$data[taxmode] = '';
	$query = "select name, company, service, item, busino, address, regdt, agreedt, printdt, price, step, doc_number from ".GD_TAX." where ordno='$_GET[ordno]' order by sno desc limit 1";
	$res = $db->query($query);
	if ( $db->count_($res) ){
		$data[taxmode] = 'taxview';
		$taxed = $db->fetch($res);
	}
	else if ( $data[taxapp] == 'Y' ) $data[taxmode] = 'taxapp';

	if($data[phoneReceiver])$data['phone'] = explode('-',$data['phoneReceiver']);
	if($data[mobileReceiver])$data['mobile'] = explode('-',$data['mobileReceiver']);
	if($data[zipcode])$data['postcode'] = explode('-',$data['zipcode']);

	if($cfg[settlePg]){
		$tmp = preg_split("/[\n]+/", $data[settlelog]);
		foreach($tmp as $v)if(preg_match('/KCP �ŷ���ȣ : /',$v))$data[tno] = str_replace('KCP �ŷ���ȣ : ','',$v);

		if($cfg['settlePg'] == 'allat' && preg_match('/�ŷ���ȣ : (.*)/', $data['settlelog'], $matched)){
			$data['tno'] = $matched[1];
		}
	}

	if($data['step2'] == 50 || $data['step2'] == 54)$resettleAble = true;
	if($cfg['autoCancelFail'] > 0){
		$ltm = toTimeStamp($data['orddt']) + 3600 * $cfg['autoCancelFail'] ;
		if($ltm < time()){
			$resettleAble = false;
		}
	}else{
		$resettleAble = false;
	}

	### PG �������л���
	if($data['step'] == 0 && $data['step2'] == 54 && $data['settlelog']){
		if(preg_match('/������� : (.*)\n/',$data['settlelog'], $matched)){
			$data['pgfailreason'] = $matched[1];
		}
	}

	### ĳ���� ����
	if(
		$cashbag['use'] == "on" &&
		$cashbag['code'] != null &&
		$data['cbyn'] == 'N' &&
		$data['step'] == '4' &&
		$data['step2'] == '0' &&
		in_array($data['settlekind'],$r_kind) &&
		$cashbagprice > 0

	) $ableCashbag = 1;

	$r_savetype = array(
		'ord' => 'orddt',
		'inc' => 'cdt',
		'deli' => 'ddt'
	);
	$r_savepriod = array(
		'mon' => 'month',
		'day' => 'day'
	);
	if($cashbag['savetype'] && $cashbag['savepriodtype'] && $cashbag['savepriod'] && $ableCashbag){
		$tmp = $data[$r_savetype[$cashbag['savetype']]];
		$tmp = strtotime($tmp);
		$tmp = strtotime("+".$cashbag['savepriod']." ".$r_savepriod[$cashbag['savepriodtype']],$tmp);
		if($tmp < time()){
			$ableCashbag = 0;
		}
	}
	if( $data[orddt] && $cashbag[savedt] && $ableCashbag ){
		if( $cashbag[savedt] > str_replace('-','',substr($data[orddt],0,10)) ){
			$ableCashbag = 0;
		}
	}

	if($data[cbyn] == 'Y'){
		$query = "select tno, add_pnt, pnt_app_time from " . GD_ORDER_OKCASHBAG . " where ordno='".$_GET['ordno']."' limit 1";
		list($data[oktno], $data[add_pnt], $data[pnt_app_time]) = $db -> fetch($query);
	}
	$authdata = md5($pg[id].$data[cardtno].$pg[mertkey]); // dacom ���̷�Ʈ ������ǥ ��� �������ڿ� ����

	$tpl->assign('NaverMileageAmount', include dirname(__FILE__).'/../../shop/proc/naver_mileage/use_amount_type_2.php');

	//���������� �����ݾ� �缳��
	$data['prn_settleprice'] = $prnSettlePrice;
	$data['goodsprice'] = $mypage_paymentDetails->getGoodsPrice(); //�� �ֹ��ݾ�
	$data['emoney'] = $mypage_paymentDetails->getUseEmoney(); //������ ���
	$data['diffPrice'] = $mypage_paymentDetails->getDiffPrice(); //��ǰ�����ݾ�
	$data['delivery'] = $mypage_paymentDetails->getDelivery(); //��ۺ�
	list($data['goodsDc'], $data['memberdc'], $data['coupon'], $data['enuri']) = $mypage_paymentDetails->getDiscount(); //��ǰ���� ȸ������ ��������, ������
	list($data['canceled_price'], $data['canceling_price'], $data['canceling_RealPrnSettlePrice']) = $mypage_paymentDetails->getCancelMultiPrice(); //��ұݾ�, ��������ݾ�, ��ҽ� �����ݾ�
	if($order->getRefundedFeeAmount() > 0) $prnSettleEtcMsg .= '(ȯ�Ҽ����� : '.number_format($order->getRefundedFeeAmount()).'��)';

	if($data['settleInflow'] == 'payco'){
		$resettleAble = false;
		$payco = Core::loader('payco');
		$data['paycoSettleType'] = $payco->getPaycoSettleType($data['payco_settle_type']); //������ ����Ÿ�� �ѱ۸� ��ȯ
	}

	$tpl->assign('authdata',$authdata);  // dacom ���̷�Ʈ ������ǥ ��� �������ڿ� ����
	$tpl->assign($data);
	$tpl->assign('item',$item);
	$tpl->assign('taxed',$taxed);
	$tpl->print_('tpl');

?>