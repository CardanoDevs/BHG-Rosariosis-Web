<?php

function CurrentMarkingPeriod($date)
{

    $sql = "SELECT marking_period_id
    FROM SCHOOL_MARKING_PERIODS
    WHERE MP='QTR'
    AND SYEAR='2020'
    AND SCHOOL_ID='1'
    AND '$date' BETWEEN START_DATE AND END_DATE
    order by marking_period_id ";

    $result = DBGet($sql);

    if ($result) {

        return array_values($result)[0]['MARKING_PERIOD_ID'];

    }

    return null;

}
