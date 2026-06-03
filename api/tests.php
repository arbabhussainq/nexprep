<?php
require_once __DIR__.'/../includes/config.php';
startSecureSession();
requireLogin();

header('Content-Type: application/json');
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db     = getDB();
$user   = currentUser();

// ---- LIST ACTIVE TESTS (student / all) ----
if ($action === 'list') {
    $subject = $_GET['subject'] ?? '';
    $exam    = $_GET['exam']    ?? '';
    $sql = "SELECT t.*,u.name AS author,
                (SELECT COUNT(*) FROM questions WHERE test_id=t.id) AS q_count
            FROM tests t JOIN users u ON t.created_by=u.id
            WHERE t.is_active='yes'";
    $p = [];
    if ($subject) { $sql .= " AND t.subject=?"; $p[] = $subject; }
    if ($exam)    { $sql .= " AND t.exam_type=?"; $p[] = $exam; }
    $sql .= " ORDER BY t.created_at DESC";
    $stmt = $db->prepare($sql); $stmt->execute($p);
    jsonResponse(true, '', ['tests' => $stmt->fetchAll()]);
}

// ---- CREATE TEST (instructor/admin) ----
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin','instructor');
    $title  = trim($_POST['title']       ?? '');
    $subj   = trim($_POST['subject']     ?? '');
    $exam   = trim($_POST['exam_type']   ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $time   = max(5, intval($_POST['time_limit']  ?? 30));
    $diff   = trim($_POST['difficulty']  ?? 'Medium');
    if (!$title||!$subj||!$exam) jsonResponse(false,'Title, subject and exam type are required.');
    if (!in_array($subj,['Physics','Chemistry','Mathematics','English'])) jsonResponse(false,'Invalid subject.');
    $db->prepare("INSERT INTO tests (title,subject,exam_type,description,time_limit,difficulty,created_by) VALUES (?,?,?,?,?,?,?)")
       ->execute([$title,$subj,$exam,$desc,$time,$diff,$user['id']]);
    jsonResponse(true,'Test created.', ['test_id' => (int)$db->lastInsertId()]);
}

// ---- UPDATE TEST ----
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin','instructor');
    $id     = intval($_POST['id'] ?? 0);
    $title  = trim($_POST['title']       ?? '');
    $exam   = trim($_POST['exam_type']   ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $time   = max(5, intval($_POST['time_limit'] ?? 30));
    $diff   = trim($_POST['difficulty']  ?? 'Medium');
    $active = $_POST['is_active'] ?? 'yes';
    if (!$id||!$title) jsonResponse(false,'Missing fields.');
    // Instructors can only edit their own tests
    if ($user['role']==='instructor') {
        $own = $db->prepare("SELECT id FROM tests WHERE id=? AND created_by=?");
        $own->execute([$id,$user['id']]);
        if (!$own->fetch()) jsonResponse(false,'You can only edit your own tests.');
    }
    $db->prepare("UPDATE tests SET title=?,exam_type=?,description=?,time_limit=?,difficulty=?,is_active=? WHERE id=?")
       ->execute([$title,$exam,$desc,$time,$diff,$active,$id]);
    jsonResponse(true,'Test updated.');
}

// ---- DELETE TEST (admin or owner instructor) ----
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin','instructor');
    $id = intval($_POST['id'] ?? 0);
    if (!$id) jsonResponse(false,'Invalid test.');
    if ($user['role']==='instructor') {
        $own = $db->prepare("SELECT id FROM tests WHERE id=? AND created_by=?");
        $own->execute([$id,$user['id']]);
        if (!$own->fetch()) jsonResponse(false,'You can only delete your own tests.');
    }
    $db->prepare("DELETE FROM tests WHERE id=?")->execute([$id]);
    jsonResponse(true,'Test deleted.');
}

// ---- ADD QUESTION ----
if ($action === 'add_question' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin','instructor');
    $tid  = intval($_POST['test_id']     ?? 0);
    $q    = trim($_POST['question']      ?? '');
    $oa   = trim($_POST['option_a']      ?? '');
    $ob   = trim($_POST['option_b']      ?? '');
    $oc   = trim($_POST['option_c']      ?? '');
    $od   = trim($_POST['option_d']      ?? '');
    $cor  = strtoupper(trim($_POST['correct'] ?? ''));
    $exp  = trim($_POST['explanation']   ?? '');
    if (!$tid||!$q||!$oa||!$ob||!$oc||!$od||!$cor) jsonResponse(false,'All fields including correct answer are required.');
    if (!in_array($cor,['A','B','C','D'])) jsonResponse(false,'Correct must be A, B, C or D.');
    $cnt = $db->prepare("SELECT COUNT(*) FROM questions WHERE test_id=?");
    $cnt->execute([$tid]);
    if ($cnt->fetchColumn() >= 25) jsonResponse(false,'Maximum 25 questions per test.');
    $db->prepare("INSERT INTO questions (test_id,question,option_a,option_b,option_c,option_d,correct,explanation) VALUES (?,?,?,?,?,?,?,?)")
       ->execute([$tid,$q,$oa,$ob,$oc,$od,$cor,$exp]);
    $db->prepare("UPDATE tests SET total_questions=(SELECT COUNT(*) FROM questions WHERE test_id=?) WHERE id=?")->execute([$tid,$tid]);
    jsonResponse(true,'Question added.', ['question_id'=>(int)$db->lastInsertId()]);
}

// ---- UPDATE QUESTION ----
if ($action === 'update_question' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin','instructor');
    $id  = intval($_POST['id'] ?? 0);
    $q   = trim($_POST['question']   ?? '');
    $oa  = trim($_POST['option_a']   ?? '');
    $ob  = trim($_POST['option_b']   ?? '');
    $oc  = trim($_POST['option_c']   ?? '');
    $od  = trim($_POST['option_d']   ?? '');
    $cor = strtoupper(trim($_POST['correct'] ?? ''));
    $exp = trim($_POST['explanation'] ?? '');
    if (!$id||!$q||!$oa||!$ob||!$oc||!$od||!$cor) jsonResponse(false,'All fields required.');
    $db->prepare("UPDATE questions SET question=?,option_a=?,option_b=?,option_c=?,option_d=?,correct=?,explanation=? WHERE id=?")
       ->execute([$q,$oa,$ob,$oc,$od,$cor,$exp,$id]);
    jsonResponse(true,'Question updated.');
}

// ---- DELETE QUESTION ----
if ($action === 'delete_question' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('admin','instructor');
    $id  = intval($_POST['id']      ?? 0);
    $tid = intval($_POST['test_id'] ?? 0);
    if (!$id) jsonResponse(false,'Invalid.');
    $db->prepare("DELETE FROM questions WHERE id=?")->execute([$id]);
    if ($tid) $db->prepare("UPDATE tests SET total_questions=(SELECT COUNT(*) FROM questions WHERE test_id=?) WHERE id=?")->execute([$tid,$tid]);
    jsonResponse(true,'Question deleted.');
}

// ---- GET QUESTIONS FOR TEST TAKING ----
if ($action === 'get_questions') {
    $tid = intval($_GET['test_id'] ?? 0);
    if (!$tid) jsonResponse(false,'Invalid test.');
    $stmt = $db->prepare("SELECT id,question,option_a,option_b,option_c,option_d FROM questions WHERE test_id=? ORDER BY id");
    $stmt->execute([$tid]);
    $qs = $stmt->fetchAll();
    shuffle($qs);
    jsonResponse(true,'',['questions'=>$qs]);
}

// ---- GET QUESTIONS WITH ANSWERS (admin/instructor) ----
if ($action === 'get_questions_admin') {
    requireRole('admin','instructor');
    $tid = intval($_GET['test_id'] ?? 0);
    if (!$tid) jsonResponse(false,'Invalid.');
    $stmt = $db->prepare("SELECT * FROM questions WHERE test_id=? ORDER BY id");
    $stmt->execute([$tid]);
    jsonResponse(true,'',['questions'=>$stmt->fetchAll()]);
}

// ---- START ATTEMPT ----
if ($action === 'start_attempt' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('student');
    $tid = intval($_POST['test_id'] ?? 0);
    if (!$tid) jsonResponse(false,'Invalid test.');
    $test = $db->prepare("SELECT id,total_questions FROM tests WHERE id=? AND is_active='yes'");
    $test->execute([$tid]);
    $t = $test->fetch();
    if (!$t) jsonResponse(false,'Test not found or inactive.');
    if ((int)$t['total_questions'] < 1) jsonResponse(false,'This test has no questions yet.');
    // Resume existing in-progress
    $ex = $db->prepare("SELECT id FROM test_attempts WHERE user_id=? AND test_id=? AND status='in_progress' LIMIT 1");
    $ex->execute([$user['id'],$tid]);
    $row = $ex->fetch();
    if ($row) jsonResponse(true,'Resuming.',['attempt_id'=>(int)$row['id']]);
    $db->prepare("INSERT INTO test_attempts (user_id,test_id,started_at,status) VALUES (?,?,NOW(),'in_progress')")->execute([$user['id'],$tid]);
    jsonResponse(true,'Started.',['attempt_id'=>(int)$db->lastInsertId()]);
}

// ---- SUBMIT ATTEMPT ----
if ($action === 'submit_attempt' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole('student');
    $aid     = intval($_POST['attempt_id'] ?? 0);
    $rawAns  = $_POST['answers']   ?? '{}';
    $timeTaken = intval($_POST['time_taken'] ?? 0);
    if (!$aid) jsonResponse(false,'Invalid attempt ID.');

    // Decode answers — keys may come as strings or ints
    $answers = json_decode($rawAns, true);
    if (!is_array($answers)) $answers = [];

    // Verify ownership
    $atSt = $db->prepare("SELECT * FROM test_attempts WHERE id=? AND user_id=? AND status='in_progress' LIMIT 1");
    $atSt->execute([$aid, $user['id']]);
    $attempt = $atSt->fetch();
    if (!$attempt) jsonResponse(false,'Attempt not found or already submitted.');

    $tid = (int)$attempt['test_id'];

    // Fetch all questions with correct answers
    $qSt = $db->prepare("SELECT id,correct FROM questions WHERE test_id=?");
    $qSt->execute([$tid]);
    $allQ = $qSt->fetchAll();

    $correct = $wrong = $skipped = $score = 0;
    $total = count($allQ);

    $db->beginTransaction();
    try {
        foreach ($allQ as $q) {
            $qid      = (int)$q['id'];
            // Match both string and int keys
            $selected = $answers[$qid] ?? $answers[(string)$qid] ?? null;
            $isCorrect = 0;
            if ($selected === null || $selected === '') {
                $skipped++;
                $selected = null;
            } elseif (strtoupper($selected) === $q['correct']) {
                $isCorrect = 1; $correct++; $score += POINTS_CORRECT;
            } else {
                $wrong++;
                // No negative marking for now
            }
            $db->prepare("INSERT INTO attempt_answers (attempt_id,question_id,selected,is_correct) VALUES (?,?,?,?)")
               ->execute([$aid, $qid, $selected, $isCorrect]);
        }

        $pct        = $total > 0 ? round(($correct/$total)*100, 2) : 0;
        $timeBonus  = max(0, POINTS_TIME_BONUS - intval($timeTaken/60));
        $rankPoints = max(0, $score + $timeBonus);

        $db->prepare("UPDATE test_attempts SET finished_at=NOW(),time_taken=?,total_questions=?,correct_answers=?,wrong_answers=?,skipped=?,score=?,percentage=?,rank_points=?,status='completed' WHERE id=?")
           ->execute([$timeTaken,$total,$correct,$wrong,$skipped,$score,$pct,$rankPoints,$aid]);

        // Leaderboard — keep best attempt
        $lb = $db->prepare("SELECT id,rank_points FROM leaderboard WHERE test_id=? AND user_id=? LIMIT 1");
        $lb->execute([$tid,$user['id']]);
        $existing = $lb->fetch();
        if (!$existing) {
            $db->prepare("INSERT INTO leaderboard (test_id,user_id,attempt_id,score,percentage,time_taken,rank_points,achieved_at) VALUES (?,?,?,?,?,?,?,NOW())")
               ->execute([$tid,$user['id'],$aid,$score,$pct,$timeTaken,$rankPoints]);
        } elseif ($rankPoints > (int)$existing['rank_points']) {
            $db->prepare("UPDATE leaderboard SET attempt_id=?,score=?,percentage=?,time_taken=?,rank_points=?,achieved_at=NOW() WHERE test_id=? AND user_id=?")
               ->execute([$aid,$score,$pct,$timeTaken,$rankPoints,$tid,$user['id']]);
        }

        // Update student profile
        $db->prepare("UPDATE student_profiles SET total_tests_taken=total_tests_taken+1,total_score=total_score+?,rank_points=rank_points+? WHERE user_id=?")
           ->execute([$score,$rankPoints,$user['id']]);

        $db->commit();
        jsonResponse(true,'Submitted!',['attempt_id'=>$aid,'correct'=>$correct,'wrong'=>$wrong,'skipped'=>$skipped,'total'=>$total,'score'=>$score,'percentage'=>$pct,'rank_points'=>$rankPoints,'time_taken'=>$timeTaken]);
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(false,'Error saving: '.$e->getMessage());
    }
}

jsonResponse(false,'Unknown action.');
