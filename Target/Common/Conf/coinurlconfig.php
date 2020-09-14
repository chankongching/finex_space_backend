<?php
// 查询币种交易信息
// https://blockchair.com/bitcoin/transaction/交易哈希即可
// 示例：https://blockchair.com/bitcoin-cash/transaction/08af040599afa0112b0f60d5bf1210dab2baa0bba3ed8316ae595f8939507d7f
// https://eosflare.io/tx/交易哈希
// 示例：https://eosflare.io/tx/64521676f1163bbf6670ace5251710c3fc1fb4f7af1878d193b8844dd372660b
return [
	'coinurl' => [
		'btc' => 'https://blockchair.com/bitcoin/transaction/',
		'eos' => 'https://eosflare.io/tx/',
		'eth' => 'https://blockchair.com/ethereum/transaction/',
		'ltc' => 'https://blockchair.com/litecoin/transaction/',
		'bch' => 'https://blockchair.com/bitcoin-cash/transaction/',
		'bsv' => 'https://blockchair.com/bitcoin-sv/transaction/',
	],
     //BCH ,BSV币种id trade_currency  
    'BCH_CURRENCY_IDS'=>[5,9],
     //检验BCH,BSV地址有效性
    'BCH_CHECK_ADDR_URL'=>'http://192.168.2.144:8888/convert',
     //BCH,BSC 地址前缀部分
    'BCH_PREFIX_STR'  =>'bchbsv',
];