.PHONY: dev

dev:
	(cd backend && php artisan serve) & (cd frontend && npm run dev)
