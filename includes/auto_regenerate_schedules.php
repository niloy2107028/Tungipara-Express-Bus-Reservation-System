<?php

// AUTO-REGENERATE SCHEDULES - Schedule automatically generate kore dibe jodi kom thake

// Check korbo schedule koto din porjonto ache
$check_query = "SELECT MAX(travel_date) as max_date FROM schedules WHERE travel_date >= CURDATE()";
$result = $conn->query($check_query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $max_date = $row['max_date'];

    if ($max_date) {
        // Koto din er schedule ache count korchi
        $days_ahead = (strtotime($max_date) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);

        // 7 din er kom thakle notun schedule generate korbo
        if ($days_ahead < 7) {
            $count_before = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE travel_date >= CURDATE()")->fetch_assoc()['count'];

            // Manually schedules generate korchi
            for ($i = 0; $i < 7; $i++) {
                $target_date = date('Y-m-d', strtotime("+$i days"));
                $day_name = date('l', strtotime($target_date));

                $sql = "INSERT INTO schedules (master_schedule_id, bus_id, route_id, departure_time, arrival_time, travel_date, available_seats, fare, status)
                        SELECT 
                            ms.master_schedule_id,
                            ms.bus_id,
                            ms.route_id,
                            ms.departure_time,
                            ms.arrival_time,
                            '$target_date',
                            b.capacity,
                            ms.fare,
                            'scheduled'
                        FROM master_schedules ms
                        JOIN buses b ON ms.bus_id = b.bus_id
                        WHERE ms.day_of_week = '$day_name'
                        AND NOT EXISTS (
                        -- already ase kina check 
                            SELECT 1 FROM schedules s 
                            WHERE s.bus_id = ms.bus_id 
                            AND s.travel_date = '$target_date' 
                            AND s.departure_time = ms.departure_time
                        )";

                $conn->query($sql);
            }

            $count_after = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE travel_date >= CURDATE()")->fetch_assoc()['count'];

            if ($count_after > $count_before) {
                $_SESSION['success'] = 'Weekly schedules regenerated successfully from master schedules!';
            }

            error_log("Auto-regenerated schedules on " . date('Y-m-d H:i:s') . " - Added " . ($count_after - $count_before) . " schedules");
        }
    } else {
        // Kono schedule nei, notun kore generate korbo
        $count_before = 0;

        for ($i = 0; $i < 7; $i++) {
            $target_date = date('Y-m-d', strtotime("+$i days"));
            $day_name = date('l', strtotime($target_date));

            $sql = "INSERT INTO schedules (master_schedule_id, bus_id, route_id, departure_time, arrival_time, travel_date, available_seats, fare, status)
                    SELECT 
                        ms.master_schedule_id,
                        ms.bus_id,
                        ms.route_id,
                        ms.departure_time,
                        ms.arrival_time,
                        '$target_date',
                        b.capacity,
                        ms.fare,
                        'scheduled'
                    FROM master_schedules ms
                    JOIN buses b ON ms.bus_id = b.bus_id
                    WHERE ms.day_of_week = '$day_name'
                    AND NOT EXISTS (
                        SELECT 1 FROM schedules s 
                        WHERE s.bus_id = ms.bus_id 
                        AND s.travel_date = '$target_date' 
                        AND s.departure_time = ms.departure_time
                    )";

            $conn->query($sql);
        }

        $count_after = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE travel_date >= CURDATE()")->fetch_assoc()['count'];

        if ($count_after > 0) {
            $_SESSION['success'] = 'Weekly schedules generated successfully from master schedules!';
        }

        error_log("Generated initial schedules on " . date('Y-m-d H:i:s') . " - Created " . $count_after . " schedules");
    }
}
