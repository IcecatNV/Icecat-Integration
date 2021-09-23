<?php //echo $object->getId(); 

use \Pimcore\Model\DataObject;

$data = DataObject\Icecat::getById($object->getId());

$store = $data->getFeatures();
$arr = [];


?>
<!DOCTYPE html>
<html lang="en">

    <head>
        <title>Bootstrap Example</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.18/owl.carousel.css"
            integrity="sha512-bKNRpBQVPhTEVuNC+jPqbBcEKwaMKCf47YyIxF/t2CBncHkeT9EE59ejt4tkXZK0uK99rlCGDQDHRDQapMBWuw=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />


        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
        <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
        <script type="text/javascript"
            src="https://res.cloudinary.com/dewn0wy2s/raw/upload/v1592669623/jquery.threesixty_envo5d.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.18/owl.carousel.js"
            integrity="sha512-woyNRzS2hPR6Pe3NZpmovZw8QM/ik7qpEOGoZseBC0zA5YbCDZuriibOj9IvPvLzj4F/F7ZWxKvP7xkQXXCBtQ=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
        <script src="/bundles/icecat/js/custom-js/360/tikslus360.js"></script>
        <script src="/bundles/icecat/js/custom-js/360/rainbow.min.js"></script>

        <style>
        body {
            color: #000;
        }

        .space-bt {
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .tabspace {
            padding-top: 28px;
        }

        .section-space {
            margin: 50px 0px;
        }

        .pro-Title h2 {
            font-size: 15px;
            font-weight: 700;
            margin-top: 30px;
        }

        .info-item {
            margin-top: 5px;
        }

        .tip-anchor-text {
            border-bottom: 1px dashed #000;
            font-weight: 600;
        }

        .rank-icon {
            width: 22px;
            height: 22px;
            display: inline-block;
            background: url(image/rank-icon-30x30.png) no-repeat;
            background-size: 100%;
            position: relative;
            right: 0;
            top: 5px;
            cursor: pointer;
        }

        .plus-icon {
            width: 14px;
            height: 14px;
            display: inline-block;
            background: url(image/plus_new.png) no-repeat;
            background-size: 100%;
            position: relative;
            right: 0;
            top: 2px;
            cursor: pointer;
        }

        .pdf i {
            font-size: 20px;
            color: red
        }

        .main-head {
            margin: 20px 0px 0px 0px;
        }

        .main-head h5 {
            margin: 10px 0 0px 0;
        }

        .main-head ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .main-head ul li {
            list-style: none;
            padding: 0;
            margin: 0 0 5px 0;
            font-size: 13px;
        }

        .side-slider .item img {
            object-fit: contain;
            height: 200px;
        }

        .carousel-control.right {
            background-image: none !important;
            color: #000;
        }

        .carousel-control.left {
            background-image: none !important;
            color: #000;
        }

        .spec-head {
            padding-left: 15px;

        }

        .spec-head h4 {
            font-weight: 600;
        }

        .inner-spec-head {
            margin-top: 15px;
            border-radius: 5px;
            border: 1px solid #c5c9e2;
        }

        .inner-spec-head .table {
            margin-bottom: 0px;
        }

        .inner-spec-head h5 {
            background: #eef1fa;
            margin: 0;
            padding: 7px 10px;
            color: #000;
            font-weight: 600;
        }

        .rot-img div {
            overflow: inherit !important;
            margin: 0 auto;
        }

        .rot-img img {
            max-width: 400px;
            margin: auto;
        }

        .brand-logo {
            width: 50px;
            position: absolute;
            top: 13px;
            /* left: 40%; */
        }

        .mb-btm {
            margin-bottom: 50px;
        }

        .text-fix {
            overflow-wrap: anywhere;
        }

        .pdf span {
            padding-right: 10px;
            display: inline-block;
            padding-bottom: 7px;
        }

        .image-left {
            /* position: absolute;
            max-width: 80%;
            top: 30px; */
            max-width: 80%;
            position: relative;
            top: 5px;
        }

        .image-right {
            max-width: 80%;
            /* position: absolute;
            top: 30px; */
            /* padding: 0px 15px 0 0; */
        }

        #owl-demo .item {
            margin: 3px;
        }

        #owl-demo .item img {
            max-width: 80px;
            display: block;
            object-fit: contain;
            max-height: 100%;
            margin: 30px auto 0 auto;
        }

        .owl-prev {
            width: 18px;
            height: 18px;
            transition: .5s;
            float: left;
            box-shadow: -2px 2px 0 #7b7b7b;
            transform: rotate(45deg);
            /* text-indent: -9000px; */
            z-index: 2;
            position: absolute;
            top: 46%;
            left: 0;
            color: #fff;
        }

        .owl-next {
            width: 18px;
            height: 18px;
            transition: .5s;
            z-index: 2;
            position: absolute;
            right: 0;
            top: 46%;
            left: auto;
            float: left;
            box-shadow: -2px 2px 0 #7b7b7b;
            transform: rotate(-135deg);
            /* text-indent: -9000px; */
            color: #fff;
        }

        .jpg-icon {
            width: 15px;
            height: 15px;
            margin-right: 4px;
        }

        .table-custom tbody tr td {
            border-top: none;
        }

        .custom-table-center {
            margin: auto;
        }

        .stop_btn {
            width: 10%;
            margin: auto;
        }

        .stop_btn button {
            width: 100%;
        }
        </style>


    </head>

    <body>
        <div class="container mb-btm">
            <div class="tabbable boxed parentTabs">
                <ul class="nav nav-tabs">

                    <?php
                $i = 0;
                foreach ($finalResult as $language) : ?>
                    <li class="<?php echo ($defaultLocale == $language['key']) ? 'active' :  ''; ?>">
                        <a data-toggle="tab" href="#lang<?= $language['key'] ?>"><?= $language['display_value'] ?></a>
                    </li>
                    <?php $i++;
                endforeach; ?>
                </ul>

                <div id="myTabContent" class="tab-content">

                    <?php
                $j = 0;

                foreach ($finalResult as $language) :
                    $arr = [];
                    $i = 0;
                ?>

                    <div class="tab-pane fade  <?php echo ($defaultLocale == $language['key']) ? 'active in' :  ''; ?> "
                        id="lang<?= $language['key'] ?>">
                        <?php if (!empty($data->getProductTitle($language['key']))) : ?>

                        <div class="col-md-12 mt-10">
                            <div class="row">

                                <div class="col-xs-12 col-md-12 col-sm-12 col-lg-12 mobile_header">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="pro-Title">
                                                <?php if (!empty($data->getBrandLogo())) { ?>
                                                <div class="col-xs-1 col-md-1 col-sm-1 col-lg-1">
                                                    <img class="brand-logo"
                                                        src="<?= $data->getBrandLogo()->getFullPath() ?>" alt="fgdfgfg"
                                                        style="width:50px;">

                                                </div>
                                                <?php } ?>
                                                <div class="col-xs-11 col-md-11 col-sm-11 col-lg-11">
                                                    <h2><?php echo  $data->getProductTitle($language['key']); ?></h2>

                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <!-- <div class="col-xs-1 col-md-1 col-sm-1 col-lg-1">
                                    </div> -->
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-xs-6 col-sm-6">
                                    <div class="col-md-12">
                                        <div class="row">

                                        </div>

                                        <div class="row  space-bt">
                                            <div class="col-md-6">

                                                <?php if (!empty($data->getBrand($language['key']))) { ?>
                                                <div class="info-item">
                                                    <span class="tip-anchor tip-anchor-text">Brand</span><span>:</span>
                                                    <a href="/en/search?supplierLocalName=HP"
                                                        title="Search HP data-sheets">
                                                        <span
                                                            class="data"><?= $data->getBrand($language['key']) ?></span>
                                                    </a>
                                                    <a class="rank-icon" title="Check ‘HP’ global rank"
                                                        href="/en/brand-statistics/HP"></a>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getProduct_Name($language['key']))) { ?>
                                                <div class="info-item">
                                                    <span class="tip-anchor tip-anchor-text">Product
                                                        Name</span><span>:</span>
                                                    <a href="/en/search?supplierLocalName=HP" title="">
                                                        <span class="data">
                                                            <?= $data->getProduct_Name($language['key']) ?></span>
                                                    </a>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getProductFamily($language['key']))) { ?>
                                                <div class="info-item">
                                                    <span class="tip-anchor tip-anchor-text">Product
                                                        Family</span><span>:</span>
                                                    <a href="/en/search?supplierLocalName=HP" title="">
                                                        <span class="data">
                                                            <?= $data->getProductFamily($language['key']) ?></span>
                                                    </a>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getProductSeries($language['key']))) { ?>
                                                <div class="info-item">
                                                    <span class="tip-anchor tip-anchor-text">Product
                                                        Series</span><span>:</span>
                                                    <a href="/en/search?supplierLocalName=HP" title="">
                                                        <span class="data">
                                                            <?= $data->getProductSeries($language['key']) ?></span>
                                                    </a>
                                                </div>
                                                <?php } ?>
                                            </div>

                                            <div class="col-md-6">
                                                <?php if (!empty($data->getProduct_Code($language['key']))) { ?>
                                                <div class="info-item">
                                                    <span class="tip-anchor tip-anchor-text">Product
                                                        Code</span><span>:</span>
                                                    <a href="/en/search?supplierLocalName=HP"
                                                        title="Search HP data-sheets">
                                                        <span class="data"><?= $data->getProduct_Code() ?></span>
                                                    </a>
                                                    <a class="plus-icon" title="" href="/en/brand-statistics/HP"></a>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getCategory($language['key']))) { ?>
                                                <div class="info-item">
                                                    <span
                                                        class="tip-anchor tip-anchor-text">Category</span><span>:</span>
                                                    <a href="/en/search?supplierLocalName=HP"
                                                        title="Search HP data-sheets">
                                                        <span
                                                            class="data"><?= $data->getCategory($language['key']) ?></span>
                                                    </a>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getIcecat_Product_Id($language['key']))) { ?>
                                                <div class="info-item">
                                                    <span class="tip-anchor tip-anchor-text">Product
                                                        ID</span><span>:</span>
                                                    <a href="/en/search?supplierLocalName=HP"
                                                        title="Search HP data-sheets">
                                                        <span
                                                            class="data"><?= $data->getIcecat_Product_Id($language['key']) ?></span>
                                                    </a>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getGtin($language['key']))) { ?>
                                                <div class="info-item">
                                                    <span
                                                        class="tip-anchor tip-anchor-text">GTIN/EAN</span><span>:</span>
                                                    <a href="/en/search?supplierLocalName=HP" title="">
                                                        <span
                                                            class="data"><?= $data->getGtin($language['key']) ?></span>
                                                    </a>
                                                </div>
                                                <?php } ?>
                                            </div>

                                            <!-- <div class="col-md-6">
                                                <?php //if (!empty($data->getData_Sheet_Quality($language['key']))) { 
                                                ?>
                                                    <div class="info-item">
                                                        <span class="tip-anchor tip-anchor-text">Data-sheet quality</span><span>:</span>
                                                        <a href="/en/search?supplierLocalName=HP" title="Search HP data-sheets">
                                                            <span class="data"><?php // $data->getData_Sheet_Quality($language['key']) 
                                                                                ?></span>
                                                        </a>
                                                    </div>
                                                <?php //} 
                                                ?>
                                                <?php // if (!empty($data->getProduct_Views($language['key']))) { 
                                                ?>
                                                    <div class="info-item">
                                                        <span class="tip-anchor tip-anchor-text">Product views</span><span>:</span>
                                                        <a href="/en/search?supplierLocalName=HP" title="Search HP data-sheets">
                                                            <span class="data"><?php // $data->getProduct_Views($language['key']) 
                                                                                ?></span>
                                                        </a>
                                                    </div>
                                                <?php //} 
                                                ?>
                                                <?php if (!empty($data->getInfo_Modified_On($language['key']))) { ?>
                                                    <div class="info-item">
                                                        <span class="tip-anchor tip-anchor-text">Info modified on</span><span>:</span>
                                                        <a href="/en/search?supplierLocalName=HP" title="Search HP data-sheets">
                                                            <span class="data"><?= $data->getInfo_Modified_On($language['key']) ?></span>
                                                        </a>
                                                    </div>
                                                <?php } ?>
                                            </div> -->
                                        </div>

                                        <div class="row  space-bt">
                                            <div class="col-md-12">
                                                <div class="info-item pdf">

                                                    <?php
                                                        if (!empty($data->getMultiMedia($language['key']))) {
                                                            $pdfElementObject = $data->getMultiMedia($language['key']);

                                                            foreach ($pdfElementObject as $pdfObject) {

                                                                $relatedPdfObject =  $pdfObject->getElement();

                                                                if ($pdfObject->getContentType($language['key']) != "application/pdf") {

                                                        ?>
                                                    <span>
                                                        <img class="jpg-icon" src="/bundles/icecat/img/jpg-icon.jpg">
                                                        <a id="" target="_blank"
                                                            href="<?= $relatedPdfObject->getFullPath() ?>">
                                                            <?= $pdfObject->getDescription(); ?>
                                                        </a>
                                                    </span>

                                                    <?php

                                                                } else {
                                                                ?>

                                                    <span><i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                                                        <a id="" target="_blank"
                                                            href="<?= $relatedPdfObject->getFullPath() ?>">
                                                            <?= $pdfObject->getDescription(); ?>
                                                        </a>
                                                    </span>

                                                    <?php
                                                                }
                                                            }
                                                        }

                                                        ?>


                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-6">
                                    <?php if (!empty($data->getGallery()->getItems())) { ?>


                                    <div id="myCarousel<?= $language['key'] ?>" class="carousel slide"
                                        data-ride="carousel">

                                        <div class="carousel-inner side-slider">
                                            <?php $i = 0;
                                                    foreach ($data->getGallery() as $img) { ?>
                                            <div class="item <?php if ($i == 0) {
                                                                                echo 'active';
                                                                            } ?>">
                                                <img src="<?= $img->getImage()->getPath() ?>/<?= $img->getImage()->getFilename() ?>"
                                                    alt="" style="width:100%;">
                                            </div>
                                            <?php $i++;
                                                    } ?>

                                        </div>


                                        <!-- Left and right controls -->
                                        <a class="left carousel-control" href="#myCarousel<?= $language['key'] ?>"
                                            data-slide="prev">
                                            <span class="glyphicon glyphicon-chevron-left"></span>
                                            <span class="sr-only">Previous</span>
                                        </a>
                                        <a class="right carousel-control" href="#myCarousel<?= $language['key'] ?>"
                                            data-slide="next">
                                            <span class="glyphicon glyphicon-chevron-right"></span>
                                            <span class="sr-only">Next</span>
                                        </a>
                                    </div>

                                    <?php } ?>
                                    <?php if (!empty($data->getGalleryIconBlock($language['key']))) : ?>


                                    <div id="owl-demo" class="owl-carousel owl-theme">
                                        <?php foreach ($data->getGalleryIconBlock($language['key']) as $blockItemMaster) {

                                                    $icon = ($blockItemMaster['galleryIcon']->getData());
                                                    $decription = ($blockItemMaster['galleryIconDescription']->getData());
                                                ?>

                                        <div class="item"><img data-toggle="tooltip" data-placement="right"
                                                title="<?= $decription ?>" src="<?= $icon->getFullPath() ?>"
                                                alt="no-image"></div>

                                        <?php }  ?>
                                    </div>
                                    <?php endif; ?>

                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-12 col-xs-12 col-sm-12">
                                    <div class="col-md-12">
                                        <div class="row">

                                        </div>


                                        <div class="row  space-bt">
                                            <div class="col-md-12">
                                                <div class="info-item pdf">
                                                    <!-- <i class="fa fa-file-pdf-o" aria-hidden="true"></i> -->
                                                    <!-- <a id="" target="_blank" href="">Product Brochure/Datasheet (0.2 MB)</a> -->
                                                </div>
                                                <?php if (!empty($data->getProductLongName($language['key']))) { ?>
                                                <div class="main-head">
                                                    <p> <b> Long product name
                                                            <?= $data->getProductTitle($language['key'])  ?>
                                                            : </b></p>
                                                    <?= $data->getProductLongName($language['key']) ?>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getWarranty($language['key']))) { ?>
                                                <div class="main-head">
                                                    <b> Warranty: </b>
                                                    <?= $data->getWarranty($language['key']) ?>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getLongDescription($language['key']))) { ?>
                                                <div class="main-head">
                                                    <p> <b> <?= $data->getProductTitle($language['key'])  ?>
                                                            : </b></p>
                                                    <?= $data->getLongDescription($language['key']) ?>
                                                </div>
                                                <?php } ?>



                                                <?php if (!empty($data->getShort_Summary($language['key']))) { ?>
                                                <div class="main-head">
                                                    <p> <b> Short summary description
                                                            <?= $data->getProductTitle($language['key'])  ?>
                                                            : </b></p>
                                                    <?= $data->getShort_Summary($language['key']) ?>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getLong_Summary($language['key']))) { ?>
                                                <div class="main-head">
                                                    <p> <b> Long summary description
                                                            <?= $data->getProductTitle($language['key'])  ?>
                                                            : </b></p>
                                                    <?= $data->getLong_Summary($language['key']) ?>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getBulletPoints($language['key']))) { ?>
                                                <div class="main-head">
                                                    <p><b> Bullet points description
                                                            <?= $data->getProductTitle($language['key'])  ?>
                                                            : </b></p>
                                                    <?= $data->getBulletPoints($language['key']) ?>
                                                </div>
                                                <?php } ?>
                                                <?php if (!empty($data->getDisclaimer($language['key']))) { ?>
                                                <div class="main-head">
                                                    <p><b> Disclaimer
                                                            <?= $data->getProductTitle($language['key'])  ?>
                                                            : </b></p>
                                                    <?= $data->getDisclaimer($language['key']) ?>
                                                </div>
                                                <?php } ?>


                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tabbable">
                            <ul class="nav nav-tabs">
                                <li class="active"><a data-toggle="tab"
                                        href="#home<?= $language['key'] ?>">Specifications</a>
                                </li>

                                <?php if (!empty(strip_tags($data->getReasons_to_buy($language['key'])))) { ?>
                                <li><a data-toggle="tab" href="#menu1<?= $language['key'] ?>">Reasons to
                                        buy</a></li>
                                <?php } ?>
                                <?php if (!empty(($data->getStoryUrl($language['key'])))) { ?>
                                <li><a data-toggle="tab" href="#menu4<?= $language['key'] ?>">Story</a></li>
                                <?php } ?>

                                <?php if (!empty($data->getTour()->getItems())) { ?>
                                <li><a data-toggle="tab" href="#menu2<?= $language['key'] ?>">3D Tour</a>
                                </li>
                                <?php } ?>

                                <?php if (!empty($data->getVideo())) {  ?>
                                <li><a data-toggle="tab" href="#menu3<?= $language['key'] ?>">Video</a></li>
                                <?php } ?>

                            </ul>
                            <div id="myTabContent" class="tab-content">
                                <div class="tab-pane fade active in" id="home<?= $language['key'] ?>">
                                    <div class="spec-head">
                                        <div class="col-md-12 col-sm-12 col-lg-12">
                                            <div class="row">
                                                <?php if (!empty($store->getGroups())) { ?>
                                                <?php
                                                        foreach ($store->getGroups() as $group) {
                                                            foreach ($group->getKeys() as $key) {
                                                                $keyConfiguration = $key->getConfiguration();
                                                                $value = $key->getValue($language['key'], true, true);
                                                                if ($value instanceof \Pimcore\Model\DataObject\Data\QuantityValue) {
                                                                    $value = (string)$value;
                                                                }
                                                                $arr[] = [
                                                                    $keyConfiguration->getTitle(),
                                                                    $value,
                                                                    $keyConfiguration->getDescription(),
                                                                ];
                                                            }
                                                        ?>
                                                <?php
                                                        }
                                                        $mainArray = [];

                                                        foreach ($arr as $key) {
                                                            $categoryArrayLanguageWise = unserialize($key[2]);
                                                            $mainArray[$categoryArrayLanguageWise[$language['key']]][] = $key;
                                                        }

                                                        $specifications = [];

                                                        try {
                                                            $specificationOrder =  ($finalJson[$language['key']]['data']['FeaturesGroups']);

                                                            foreach ($specificationOrder as $specificationOrderRow) {
                                                                $keyName = $specificationOrderRow['FeatureGroup']['Name']['Value'];

                                                                if (isset($mainArray[$keyName]))
                                                                    $specifications[$keyName] = $mainArray[$keyName];
                                                            }
                                                        } catch (\Throwable $th) {

                                                            echo "Something  went wrong .";
                                                            die;
                                                        }



                                                        $keys = array_keys($specifications);

                                                        $halved = array_chunk($specifications, ceil(count($mainArray) / 2));

                                                        $firstArray =  $halved[0];
                                                        if (isset($halved[1])) {
                                                            $secondArray =   $halved[1];
                                                        }
                                                        ?>
                                                <div
                                                    class="<?= (!isset($halved[1]) ? "col-md-12 col-sm-12 col-lg-12" : "col-md-6 col-sm-6 col-lg-6") ?> ">
                                                    <?php
                                                            $i = 0;
                                                            foreach ($firstArray as $mainArrayKey => $values) { ?>
                                                    <div class="inner-spec-head">
                                                        <table class="table table-custom">
                                                            <div>
                                                                <h5><?= $keys[$i] ?></h5>
                                                            </div>
                                                            <tbody>
                                                                <?php foreach ($values as $features) { ?>
                                                                <tr>
                                                                    <td><?= $features[0] ?>
                                                                    </td>
                                                                    <td class="text-fix">
                                                                        <?php
                                                                                        if (is_array($features[1])) : ?>
                                                                        <?php echo implode(',', $features[1]);
                                                                                        elseif (is_bool($features[1])) :
                                                                                            if ($features[1] == true) :
                                                                                                echo '<i class="fa fa-check" style="font-size:16px;color:green"></i>';
                                                                                            else :
                                                                                                echo '<i class="fa fa-times" style="font-size:16px;color:red"></i>';
                                                                                            endif;

                                                                                        else : echo $features[1];
                                                                                        endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <?php $i++;
                                                            } ?>
                                                </div>
                                                <?php if (isset($secondArray)) { ?>
                                                <div class="col-md-6 col-sm-6 col-lg-6">
                                                    <?php foreach ($secondArray as $mainArrayKey2 => $values) { ?>
                                                    <div class="inner-spec-head">
                                                        <table class="table table-custom">
                                                            <div>
                                                                <h5><?= $keys[$i]; ?></h5>
                                                            </div>
                                                            <tbody>
                                                                <?php foreach ($values as $features) { ?>
                                                                <tr>
                                                                    <td><?= $features[0] ?>
                                                                    </td>
                                                                    <td class="text-fix">
                                                                        <?php
                                                                                            if (is_array($features[1])) : ?>
                                                                        <?php echo implode(',', $features[1]);
                                                                                            elseif (is_bool($features[1])) :
                                                                                                if ($features[1] == true) :
                                                                                                    echo '<i class="fa fa-check" style="font-size:16px;color:green"></i>';
                                                                                                else :
                                                                                                    echo '<i class="fa fa-times" style="font-size:16px;color:red"></i>';
                                                                                                endif;

                                                                                            else : echo $features[1];
                                                                                            endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php } ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <?php $i++;
                                                                } ?>
                                                </div>
                                                <?php } ?>
                                                <?php } else { ?>
                                                <div class="col-md-6 col-sm-6 col-lg-6">
                                                    <div class="inner-spec-head">
                                                        <table class="table table-custom">
                                                            <div>
                                                                <h5>Features</h5>
                                                            </div>
                                                            <tbody>
                                                                <tr>
                                                                    <?php echo "<h5>Empty !!! </h5>"; ?>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="menu1<?= $language['key'] ?>">

                                    <div class="spec-head">

                                        <?php if (!empty(strip_tags($data->getReasons_to_buy($language['key'])))) {

                                            ?>

                                        <?= $data->getReasons_to_buy($language['key']) ?>
                                        <?php } else { ?>
                                        <h5>Empty!!!</h5>
                                        <?php }   ?>



                                    </div>




                                </div>
                                <div class="tab-pane fade" id="menu2<?= $language['key'] ?>">
                                    <table border="0" cellpadding="0" cellspacing="0" class="custom-table-center">


                                        <tr>
                                            <td style="width:300px; height:300px">
                                                <span class="rot-img">
                                                    <img alt="" src="" class="product1">
                                                    <span>
                                            </td>

                                        </tr>


                                    </table>
                                    <div class="dvImages" style="display: none;" style="width:300px; height:300px">


                                        <?php if (!empty($data->getTour()->getItems())) { ?>
                                        <?php $i = 0;
                                                foreach ($data->getTour() as $img) { ?>
                                        <img src="<?= $img->getImage()->getPath() ?>/<?= $img->getImage()->getFilename() ?>"
                                            alt="" style="width:50%;">
                                        <?php } ?>
                                        <?php } else { ?>

                                        <h5>Empty!!!</h5>


                                        <?php }   ?>

                                    </div>

                                    <div class="col-md-12">
                                        <div class="stop_btn">
                                            <button type="button" onclick="toggleThreeSixty(this)">
                                                <i class="fa fa-pause"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="menu3<?= $language['key'] ?>">

                                    <div class="spec-head">

                                        <?php
                                            ///  $newAsset = new Pimcore\Model\Asset();
                                            //   newe \Pimcore\Model\Asset\Video::

                                            if (!empty($data->getVideo())) {


                                                $videoData = $data->getVideo()->getData();
                                                $videoAssetId = $videoData->getId();
                                                $videoAsset = Pimcore\Model\Asset::getById($videoAssetId);

                                                $path = ($videoAsset->getFullPath());
                                            ?>
                                        <video width="320" height="240" controls>
                                            <source src="<?= $path ?>" type="video/mp4">
                                        </video>
                                        <?php
                                            } else { ?>

                                        <h5>Empty!!!</h5>


                                        <?php }   ?>






                                    </div>



                                </div>
                                <div class="tab-pane fade" id="menu4<?= $language['key'] ?>">

                                    <div class="spec-head" id="my-div-frame-wrapper">

                                        <iframe src="<?= $data->getStoryUrl($language['key']) ?>" scrolling="yes"
                                            style="width: 100%; overflow: hidden; resize: both; height:800px;  border:none"
                                            title="Iframe Example" id="myframe">
                                        </iframe>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php else : ?>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <h3>You are not subscribed to this language </h3>
                        </div>
                        <?php endif; ?>
                    </div>



                    <?php
                    ++$j;
                    ++$i;
                endforeach; ?>



                </div>
            </div>
        </div>




        <script type="text/javascript">
        var arr = new Array();
        var isStoped = true;
        var i = 0;
        $(document).ready(function() {
            //Load the image URLs into an Array.

            $(".dvImages img").each(function() {
                arr.push($(this).attr("src"));
            });

            //Set the first image URL as source for the Product.
            $(".product1").attr("src", arr[0]);



        });

        function timeout() {

            if (isStoped) {


                setTimeout(function() {

                    i++;
                    $(".product1").attr('src', arr[i]);
                    timeout();
                    if (i == arr.length - 1) {
                        i = 0;
                    }
                }, 120);
            }
        }
        timeout();

        function toggleThreeSixty(obj) {

            if (isStoped) {
                isStoped = false;
                $(obj).html('<i class = "fa fa-play"></i> ');
            } else {
                isStoped = true;
                $(obj).html('<i class = "fa fa-pause"></i> ');
            }
            timeout();
        }
        </script>

        <script>
        $("ul.nav-tabs a").click(function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
        </script>
        <script>
        $(document).ready(function() {

            $("#owl-demo").owlCarousel({

                autoPlay: 3000, //Set AutoPlay to 3 seconds
                items: 4,
                itemsDesktop: [1199, 3],
                itemsDesktopSmall: [979, 3],
                navigation: true,
            });

        });
        // $(document).ready(function() {
        //     $('iframe').load(function() {
        //         this.style.height =
        //             this.contentWindow.document.body.offsetHeight + 'px';
        //     });
        // });
        $(document).ready(function() {
            // alert(document.getElementById("myframe").contentWindow.document.body.scrollHeight + 'px');
        });
        </script>


    </body>

</html>