<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%user}}`.
 */
class m200518_084616_add_allowance_updated_at_column_to_user_table extends Migration
{
     /**
     * {@inheritdoc}
     */
    public function up()
    {
        $this->addColumn('user', 'allowance_updated_at_column', $this->dateTime());
    }

    /**
     * {@inheritdoc}
     */
    public function down()
    {
        $this->dropColumn('user', 'allowance_updated_at_column');
    }
}
