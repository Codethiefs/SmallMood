<?php if ( ! defined ( 'SMALL_PATH' ) ) exit ( ) ; ?><!DOCTYPE html>
<html>

<head lang="en">
    <meta charset="UTF-8">
    <title>ttttttt</title>
</head>

<body>



    <?php if ( is_array ( $list ) ) : $i = 0 ; $__LIST__ = $list ; if ( count ( $__LIST__ ) ==0 ) : echo "" ; else : foreach ( $__LIST__ as $key=>$vo ) : $mod = ( $i % 2 ) ; ++$i ; if ( is_array ( $vo ) ) : $i = 0 ; $__LIST__ = $vo ; if ( count ( $__LIST__ ) ==0 ) : echo "" ; else : foreach ( $__LIST__ as $key=>$voo ) : $mod = ( $i % 2 ) ; ++$i ; echo ( $ttt ) ; ?>                                                      <?php echo ( $voo [ "id" ] ) ; ?>:<?php echo ( $voo [ "name" ] ) ; ?><br/><?php endforeach ; endif ; else : echo "" ; endif ; endforeach ; endif ; else : echo "" ; endif ; ?>

    <p>
        <?php echo ( strtoupper ( $_SERVER [ 'SCRIPT_NAME' ] ) ) ; ?>
    </p>



<div class="footer">
    footer
</div>

</body>
</html>