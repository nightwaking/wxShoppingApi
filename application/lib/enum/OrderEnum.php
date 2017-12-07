<?php

namespace app\lib\enum;

class OrderEnum{
	//待支付
	const UNPAID = 1;
	//已支付
	const PAID = 2;
	//已发货
	const DELIVERED = 3;
	//支付后库存不足
	const PAID_BUT_OUT_OF = 4;
}