#!/bin/bash

# Gateway API Test Script
# Bu script Gateway'in temel fonksiyonlarını test eder

BASE_URL="http://localhost:5000"

echo "🚀 Gateway API Test Script"
echo "=========================="
echo ""

# Test 1: Gateway Info
echo "📋 Test 1: Gateway Info Endpoint"
curl -s $BASE_URL/ | jq '.'
echo ""
echo ""

# Test 2: Health Check
echo "💚 Test 2: Health Check"
curl -s $BASE_URL/health | jq '.'
echo ""
echo ""

# Test 3: Ready Check
echo "✅ Test 3: Ready Check"
curl -s $BASE_URL/health/ready | jq '.'
echo ""
echo ""

# Test 4: Live Check
echo "💓 Test 4: Live Check"
curl -s $BASE_URL/health/live | jq '.'
echo ""
echo ""

# Test 5: Metrics
echo "📊 Test 5: Prometheus Metrics"
curl -s $BASE_URL/metrics | head -20
echo ""
echo "... (truncated)"
echo ""
echo ""

# Test 6: CORS Preflight
echo "🌐 Test 6: CORS Preflight Request"
curl -s -X OPTIONS $BASE_URL/api/products \
  -H "Origin: http://localhost:3000" \
  -H "Access-Control-Request-Method: GET" \
  -I | grep -i "access-control"
echo ""
echo ""

# Test 7: Rate Limiting
echo "⚡ Test 7: Rate Limiting (sending 10 requests)"
for i in {1..10}; do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL/)
  echo "Request $i: HTTP $STATUS"
done
echo ""
echo ""

# Test 8: Security Headers
echo "🔒 Test 8: Security Headers"
curl -s -I $BASE_URL/ | grep -E "X-Content-Type-Options|X-Frame-Options|X-XSS-Protection"
echo ""
echo ""

# Test 9: Correlation ID
echo "🔗 Test 9: Correlation ID"
RESPONSE=$(curl -s -D - $BASE_URL/ -o /dev/null)
echo "$RESPONSE" | grep -i "X-Correlation-ID"
echo ""
echo ""

# Test 10: Swagger UI
echo "📚 Test 10: Swagger Documentation"
STATUS=$(curl -s -o /dev/null -w "%{http_code}" $BASE_URL/swagger/index.html)
if [ "$STATUS" -eq 200 ]; then
  echo "✅ Swagger UI is accessible at $BASE_URL/swagger"
else
  echo "⚠️  Swagger UI returned HTTP $STATUS"
fi
echo ""
echo ""

echo "=========================="
echo "✅ All tests completed!"
echo ""
echo "Next steps:"
echo "1. Start downstream services (User, Order, Product, Auth services)"
echo "2. Test reverse proxy routing:"
echo "   curl $BASE_URL/api/users"
echo "   curl $BASE_URL/api/products"
echo "3. Test authentication with JWT token"
echo ""
