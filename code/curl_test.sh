#!/bin/bash

BASE_URL="http://localhost/api"

echo "========================================="
echo "     API Testing Script"
echo "========================================="
echo ""

# Variables to store tokens
ALICE_TOKEN=""
BOB_TOKEN=""
TONY_TOKEN=""
ADMIN_TOKEN=""

# Helper function to print test headers
print_test() {
    echo ""
    echo "-------------------------------------------"
    echo "TEST: $1"
    echo "-------------------------------------------"
}

#Register Users
print_test "1. Register alice"
curl -X POST "${BASE_URL}/users/register.php" \
     -H "Content-Type: application/json" \
     -d '{"username":"alice","password":"123456"}'
echo ""

print_test "2. Register bob"
curl -X POST "${BASE_URL}/users/register.php" \
     -H "Content-Type: application/json" \
     -d '{"username":"bob","password":"dancer"}'
echo ""

print_test "3. Register tony"
curl -X POST "${BASE_URL}/users/register.php" \
     -H "Content-Type: application/json" \
     -d '{"username":"tony","password":"alleycat"}'
echo ""

#Login Users and Extract Tokens
print_test "4. Login alice"
ALICE_RESPONSE=$(curl -s -X POST "${BASE_URL}/users/login.php" \
     -H "Content-Type: application/json" \
     -d '{"username":"alice","password":"123456"}')
echo "$ALICE_RESPONSE"
ALICE_TOKEN=$(echo "$ALICE_RESPONSE" | grep -o '"token":"[^"]*' | sed 's/"token":"//')
echo "Alice Token: $ALICE_TOKEN"
echo ""

print_test "5. Login bob"
BOB_RESPONSE=$(curl -s -X POST "${BASE_URL}/users/login.php" \
     -H "Content-Type: application/json" \
     -d '{"username":"bob","password":"dancer"}')
echo "$BOB_RESPONSE"
BOB_TOKEN=$(echo "$BOB_RESPONSE" | grep -o '"token":"[^"]*' | sed 's/"token":"//')
echo "Bob Token: $BOB_TOKEN"
echo ""

print_test "6. Login tony"
TONY_RESPONSE=$(curl -s -X POST "${BASE_URL}/users/login.php" \
     -H "Content-Type: application/json" \
     -d '{"username":"tony","password":"alleycat"}')
echo "$TONY_RESPONSE"
TONY_TOKEN=$(echo "$TONY_RESPONSE" | grep -o '"token":"[^"]*' | sed 's/"token":"//')
echo "Tony Token: $TONY_TOKEN"
echo ""

print_test "7. Login admin"
ADMIN_RESPONSE=$(curl -s -X POST "${BASE_URL}/users/login.php" \
     -H "Content-Type: application/json" \
     -d '{"username":"admin","password":"admin123"}')
echo "$ADMIN_RESPONSE"
ADMIN_TOKEN=$(echo "$ADMIN_RESPONSE" | grep -o '"token":"[^"]*' | sed 's/"token":"//')
echo "Admin Token: $ADMIN_TOKEN"
echo ""

#Send Friend Requests
print_test "8. Tony sends friend request to Bob"
curl -X POST "${BASE_URL}/friends/send_request.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $TONY_TOKEN" \
     -d '{"receiverId": 5}'
echo ""

print_test "9. Tony sends friend request to Alice"
curl -X POST "${BASE_URL}/friends/send_request.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $TONY_TOKEN" \
     -d '{"receiverId": 4}'
echo ""

#View Friend Requests
print_test "10. Bob views friend requests"
curl -X GET "${BASE_URL}/friends/list_requests.php" \
     -H "Authorization: Bearer $BOB_TOKEN"
echo ""

print_test "11. Alice views friend requests"
curl -X GET "${BASE_URL}/friends/list_requests.php" \
     -H "Authorization: Bearer $ALICE_TOKEN"
echo ""

#Accept/Reject Friend Requests
print_test "12. Bob accepts Tony's friend request"
curl -X PUT "${BASE_URL}/friends/accept_request.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $BOB_TOKEN" \
     -d '{"requestId": 1}'
echo ""

print_test "13. Alice rejects Tony's friend request"
curl -X PUT "${BASE_URL}/friends/reject_request.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $ALICE_TOKEN" \
     -d '{"requestId": 2}'
echo ""

#View Friends Lists
print_test "14. View Alice's friends"
curl -X GET "${BASE_URL}/friends/list_friends.php?userId=4" \
     -H "Authorization: Bearer $ALICE_TOKEN"
echo ""

print_test "15. View Bob's friends"
curl -X GET "${BASE_URL}/friends/list_friends.php?userId=5" \
     -H "Authorization: Bearer $BOB_TOKEN"
echo ""

print_test "16. View Tony's friends"
curl -X GET "${BASE_URL}/friends/list_friends.php?userId=6" \
     -H "Authorization: Bearer $TONY_TOKEN"
echo ""

#Remove Friend
print_test "17. Tony removes Bob as friend"
curl -X DELETE "${BASE_URL}/friends/remove_friend.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $TONY_TOKEN" \
     -d '{"friendId": 5}'
echo ""

#Change Password
print_test "18. Alice changes password"
curl -X PUT "${BASE_URL}/users/change_password.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $ALICE_TOKEN" \
     -d '{"oldPassword": "123456","newPassword": "654321"}'
echo ""

#Admin View All Users
print_test "19. Admin views all users"
curl -X GET "${BASE_URL}/users/get_users.php" \
     -H "Authorization: Bearer $ADMIN_TOKEN"
echo ""

#Delete User Account
print_test "20. Tony deletes own account"
curl -X DELETE "${BASE_URL}/users/delete_self.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $TONY_TOKEN" \
     -d '{"password": "alleycat"}'
echo ""

#Admin Delete User
print_test "21. Admin deletes Bob's account"
curl -X DELETE "${BASE_URL}/users/admin_delete.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $ADMIN_TOKEN" \
     -d '{"id": 5}'
echo ""

#Logout
print_test "22. Alice logs out"
curl -X POST "${BASE_URL}/users/logout.php" \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer $ALICE_TOKEN"
echo ""

echo ""
echo "========================================="
echo "       All Tests Completed!"
echo "========================================="


