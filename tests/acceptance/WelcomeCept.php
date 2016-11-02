<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('看到 hellow nanometre');
$I->sendGET('/');
$I->seeResponseEquals("Hellow nanometre");
