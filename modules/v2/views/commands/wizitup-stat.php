<?php
$this->title = 'WizItUp stats';
?>

<?php if($type == 'user'){?>
<table class="table table-striped table-hover table-bordered">
    <thead>
    <tr>
        <th>S/N (<?=count($data)?>)</th>
        <th>Name</th>
        <th>Student Code</th>
        <th>Video Count</th>
        <th>Parent Email</th>
        <th>School Name</th>
        <th>Student Subscription</th>
        <th>School Subscription</th>

    </tr>
    </thead>
    <tbody>
    <?php foreach ($data as $key=>$item) { ?>
        <tr>
            <td><?=$key+1?></td>
            <td><?=$item['firstname'].' '.$item['lastname']?></td>
            <td><?=$item['code']?></td>
            <td><?=$item['videos_count']?></td>
            <td><?=$item['email']?></td>
            <td><?=$item['name']?></td>
            <td><?=$item['subscription_plan'].' ('.$item['subscription_expiry'].')'?></td>
            <td><?=$item['subscription_status'].' ('.$item['school_sub_expiry'].')'?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>
<?php }else{ ?>

    <table class="table table-striped table-hover table-bordered">
        <thead>
        <tr>
            <th>S/N (<?=count($data)?>)</th>
            <th>User count</th>
            <th>Content ID</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $key=>$item) { ?>
            <tr>
                <td><?=$key+1?></td>
                <td><?=$item['user_count']?></td>
                <td><?=$item['content_id']?></td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

<?php } ?>
