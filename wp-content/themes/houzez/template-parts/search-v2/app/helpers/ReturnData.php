<?php

    class ReturnData {
        public function rJSON($status,$message, $data) {
            return json_encode(array(
                'status' => $status,
                'message' => $message,
                'data' => $data
            ));
        }
     }

?>