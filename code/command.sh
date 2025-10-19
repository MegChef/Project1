# This is a placeholder for your curl commands.

#Register a user:
curl -X POST http://localhost:3000/api/users/register \
  -H "Content-Type: application/json" \
  -d '{"username":"root","password":"sillypassword"}'

#Login and get a token:
curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"alice","password":"password123"}'

#Send friend request
curl -X POST http://localhost:3000/api/friends/request/OTHER_USER_ID \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

#View friend request as bob
curl -X GET http://localhost:3000/api/friends/requests \
  -H "Authorization: Bearer <bob_token>"

#Accept friend request as bob
curl -X POST http://localhost:3000/api/friends/accept/REQUEST_ID \
  -H "Authorization: Bearer <bob_token>"

#Reject friend request as bob 
curl -X POST http://localhost:3000/api/friends/reject/REQUEST_ID \
  -H "Authorization: Bearer <bob_TOKEN>"

#View friends as alice or bob
curl -X GET http://localhost:3000/api/friends \
  -H "Authorization: Bearer <bob_token>"

#Remove a friend (alice removes bob)
curl -X DELETE http://localhost:3000/api/friends/USER_ID \
  -H "Authorization: Bearer <alice_token>"

#Delete own profile 
curl -X DELETE http://localhost:3000/api/users/me \
  -H "Authorization: Bearer <USER_TOKEN>"

#Change password
curl -X POST http://localhost:3000/api/users/me/password \
  -H "Authorization: Bearer <USER_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"password":"newpassword123"}'

#User logs out
curl -X POST http://localhost:3000/api/auth/logout \
  -H "Authorization: Bearer <USER_TOKEN>"

#Admin deletes user
curl -X DELETE http://localhost:3000/api/users/USER_ID \
  -H "Authorization: Bearer <ADMIN_TOKEN>"

#View users (admin only):
curl -X GET http://localhost:3000/api/users \
  -H "Authorization: Bearer YOUR_TOKEN"
