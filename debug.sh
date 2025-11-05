#!/bin/bash

# PHP Debug Helper Script
# This script helps start the PHP development server with Xdebug enabled

echo "🐛 PHP Debug Helper"
echo "==================="
echo ""

# Check if Xdebug is installed
if php -v | grep -q "Xdebug"; then
    echo "✅ Xdebug is installed"
    XDEBUG_VERSION=$(php -v | grep "Xdebug" | awk '{print $2}' | head -1)
    echo "   Version: $XDEBUG_VERSION"
else
    echo "❌ Xdebug is NOT installed"
    echo "   Install with: pecl install xdebug"
    exit 1
fi

echo ""
echo "🔧 Xdebug Configuration:"
echo "   Mode: $(php -r 'echo ini_get("xdebug.mode");')"
echo "   Port: $(php -r 'echo ini_get("xdebug.client_port");')"
echo "   Host: $(php -r 'echo ini_get("xdebug.client_host");')"
echo "   IDE Key: $(php -r 'echo ini_get("xdebug.idekey");')"
echo ""

# Check which mode to run
if [ "$1" == "--test" ]; then
    echo "🧪 Running tests with debugger..."
    echo "   Make sure your IDE is listening on port 9003"
    echo ""
    php -dxdebug.mode=debug ./vendor/bin/phpunit "${@:2}"
elif [ "$1" == "--help" ] || [ "$1" == "-h" ]; then
    echo "Usage:"
    echo "  ./debug.sh              Start development server with Xdebug"
    echo "  ./debug.sh --test       Run PHPUnit tests with Xdebug"
    echo "  ./debug.sh --test FILE  Run specific test file with Xdebug"
    echo ""
    echo "Examples:"
    echo "  ./debug.sh"
    echo "  ./debug.sh --test"
    echo "  ./debug.sh --test tests/Chat/ChatServiceTest.php"
else
    echo "🚀 Starting PHP development server with Xdebug..."
    echo "   URL: http://localhost:8000"
    echo "   Document Root: public/"
    echo ""
    echo "📍 To debug:"
    echo "   1. Set breakpoints in your code"
    echo "   2. In your IDE: Press Cmd+Shift+P → 'Debug: Select and Start Debugging' → 'Listen for Xdebug'"
    echo "   3. Make a request: curl http://localhost:8000/api/health"
    echo ""
    echo "Press Ctrl+C to stop the server"
    echo "=================="
    echo ""
    
    php -dxdebug.mode=debug -S localhost:8000 -t public/ public/router.php
fi

