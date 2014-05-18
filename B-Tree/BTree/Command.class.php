<?php
interface   BTree_Command {

    public  static  function getInstance (BTree_Store $store, BTree_Options $options);

    public  function call ($params);
}