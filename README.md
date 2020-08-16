## Monobank API SDK for php clients.

You should init client object with **3** parameters:
**api key, base api url, store id**

## Allowed methods

- **validateClientByPhone()**
  - **Input:** ```String $phone (pay your attention, you may use full number like +380931231212)```
  - **Output**: ```[success, (error | clientExist, clientData)]```

- **initOrderPayment()**
  - **Input**: ```[String store_order_id; String client_phone, Array invoice, Array available_programs, Array products, numeric total_sum, ?String result_callback]```
  - **Output**: ```[success, (error | orderId)]```

- **checkOrderStatus()**
  - **Input**: 
```String $orderId (order identifier on monobank side, witch you get while you initialize order payment ( **method initOrderPayment()** ))```
  - **Output**: ```[success, (error | success, orderId, state, subState) ]```

- **checkPaymentByOrder()**
  - **Input**: ```String $orderId (order identifier on monobank side, witch you get while you initialize order payment ( **method initOrderPayment()** ))```
  - Output: ```[success, (error | possibleReturnToCard, fullSumPayed )]```

- **confirmOrderProcessing()**
  - **Input**: ```String $orderId (order identifier on monobank side, witch you get while you initialize order payment ( **method initOrderPayment()** ))```
  - **Output**: ```[success, (error | success, orderId, state, subState)]```

- **rejectOrderProcessing()**
  - **Input**: ```String $orderId (order identifier on monobank side, witch you get while you initialize order payment ( **method initOrderPayment()** ))```
  - **Output**: ```[success, (error | success, orderId, state, subState)]```

- **rejectOrder()**
  - **Input**: ```[String order_id; bool return_money_to_card, String store_return_id, numeric sum]```
  - **Output**: ```[success, (- | error)]```

