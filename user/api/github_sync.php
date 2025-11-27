<?php
require_once __DIR__ . '/../includes/security_init.php';
/**
 * GitHub API Endpoint
 * Handles GitHub integration requests
 */

header("Content-Type: application/json");
require_once "../github_service.php";
require_once "../../Login/Login/db.php";

session_start();

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];

switch ($method) {
    case "POST":
        $input = json_decode(file_get_contents("php://input"), true);

        if (isset($input["action"]) && $input["action"] === "sync") {
            $username = $input["username"] ?? "";

            if (empty($username)) {
                echo json_encode(["success" => false, "message" => "Username is required"]);
                exit;
            }

            $result = syncGitHubData($conn, $_SESSION["user_id"], $username);
            echo json_encode($result);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
        break;

    case "GET":
        if (isset($_GET["test"])) {
            $isConnected = testGitHubConnectivity();
            echo json_encode([
                "success" => true,
                "connected" => $isConnected,
                "message" => $isConnected ? "GitHub API is accessible" : "GitHub API is not accessible"
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid request"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
