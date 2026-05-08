Write-Host "Setting up LAMP Stack with Docker..." -ForegroundColor Green

# Check Docker
if (!(Get-Command docker -ErrorAction SilentlyContinue)) {
    Write-Host "Docker is not installed. Please install Docker Desktop." -ForegroundColor Red
    exit
}

# Build containers
Write-Host "Building containers..."
docker-compose build

# Start containers
Write-Host "Starting containers..."
docker-compose up -d

# Wait a few seconds for DB
Write-Host "Waiting for services to initialize..."
Start-Sleep -Seconds 10

# Show status
Write-Host "Running containers:"
docker ps

Write-Host ""
Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "Web App: http://localhost:8080"
Write-Host "phpMyAdmin: http://localhost:8081"
Write-Host "MySQL:"
Write-Host "   Host: localhost"
Write-Host "   User: user"
Write-Host "   Password: password"
Write-Host "   DB: lostandfound"