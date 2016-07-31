<?php
include '../_header.php';

$ordno = $_GET['ordno'] ? $_GET['ordno'] : $_POST['ordno'];
$reOrder = new reOrder;

// 주문서 체크 함수 호출
$result = $reOrder->chk_order($ordno);

if ($result['itemCount'] == $result['added'] && $result['price_result'] == 0) {
	msg($result['added']."개의 상품을 장바구니에 담았습니다.","../goods/cart.php");
}
else if ($result['itemCount'] == $result['added'] && $result['price_result'] > 0) {
	msg($result['added']."개의 상품을 장바구니에 담았습니다. \\n판매 가격이 변경된 상품은 장바구니에서 확인하실 수 있습니다.","../goods/cart.php");
}
else if ($result['added'] > 0 && $result['price_result'] == 0) {
	msg("재주문 가능한 상품을 장바구니에 담았습니다. \\n(품절, 옵션변경 상품은 재주문 불가)","../goods/cart.php");
}
else if ($result['added'] > 0 && $result['price_result'] > 0) {
	msg("재주문 가능한 상품을 장바구니에 담았습니다. \\n(품절, 옵션변경 상품은 재주문 불가)\\n판매 가격이 변경된 상품은 장바구니에서 확인하실 수 있습니다.","../goods/cart.php");
}
else if ($result['added'] == 0) {
	msg("판매중단, 옵션 변경으로 모든 상품의 재 주문이 불가능 합니다.",-1);
}
else {}
?>