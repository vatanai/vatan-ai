# Instagram Automation — Final Setup Checklist
**Status:** 90% Complete — Ready for Final Configuration  
**Time Remaining:** ~30 minutes to full activation

---

## ✅ What's Already Done

- [x] Laravel database schema created
- [x] Eloquent model with query scopes built
- [x] API controller with 4 endpoints implemented
- [x] Routes registered in `routes/web.php`
- [x] All files copied to your project
- [x] n8n workflow JSON prepared
- [x] API credentials obtained
- [x] Complete documentation prepared

---

## 🎯 Complete These 3 Steps to Go Live

### Step 1: Import Workflow into n8n ⏱️ (10 min)

**Open n8n:** https://n8n-mohsen.cldv.dev

**Method A - Via UI (Recommended):**
1. Click **Create workflow** dropdown
2. Look for **"Import from JSON"** or **"Import"** option
3. Copy the entire contents of `n8n_instagram_workflow.json` 
4. Paste into the import dialog
5. Click **Import**

**Method B - Via API (If UI import not available):**
```bash
# Run this in your terminal (requires n8n API key)
curl -X POST https://n8n-mohsen.cldv.dev/api/v1/workflows \
  -H "Content-Type: application/json" \
  -H "X-N8N-API-KEY: your_api_key" \
  -d @n8n_instagram_workflow.json
```

✅ **Verify:** Workflow appears in your workflows list

---

### Step 2: Configure Credentials 🔑 (10 min)

**In the imported workflow, edit each node:**

1. **Find node: "Composio: Get Instagram Comments"**
   - Authentication method: Generic (API Key)
   - API Key: `ck_...`
   - Header: `Authorization`

2. **Find node: "Generate AI Response with OpenRouter"**
   - HTTP Request type
   - Add Header: `Authorization`
   - Value: `Bearer sk-or-v1-...`

3. **Find node: "Save Comment to Laravel DB"**
   - URL: `http://localhost:8000/api/v1/instagram/webhook` (for local testing)
   - OR: `https://aivatan.com/api/v1/instagram/webhook` (for production)
   - Method: POST
   - Content-Type: application/json

4. **Find node: "Save Skipped Comment"**
   - Same URL as above

✅ **Verify:** Each node shows "Connected" or no error indicators

---

### Step 3: Test & Activate 🚀 (10 min)

**Test the workflow:**
1. Click **Test Workflow** button in n8n
2. Expected output: Should show either:
   - Received 0 comments (normal if no new Instagram comments)
   - Received N comments with DM sent status

3. If errors appear, check:
   - API keys are correct
   - Composio API is accessible
   - Laravel webhook URL is correct

**Activate for production:**
1. Once test passes, click **Activate** button
2. Workflow will run every 1 minute automatically
3. Monitor executions in the **Executions** tab

✅ **Verify:** Green checkmark shows workflow is active

---

## 📊 Expected Behavior After Activation

**Minute 1-2:**
- Workflow starts checking Instagram for comments
- Database may be empty if no comments exist

**When someone comments:**
- Composio retrieves the comment
- If comment contains keyword (محصول/قیمت/خرید/etc): 
  - Checks if user follows your account
  - If follows → AI generates response → Sends as DM
  - If not → Skips silently
- All activity logged to `instagram_comments` table

**You can verify with:**
```bash
# Get statistics
curl http://localhost:8000/api/v1/instagram/stats

# List all comments
curl http://localhost:8000/api/v1/instagram/comments?limit=50

# Check database directly
SELECT * FROM instagram_comments ORDER BY created_at DESC LIMIT 10;
```

---

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| "Invalid API Key" error in Composio node | Verify API key: `ck_...` |
| 401 error from OpenRouter | Check Bearer token format: `Bearer sk-or-v1-...` |
| Workflow fails to execute | Check n8n logs for full error message |
| No comments being retrieved | May need to wait for real Instagram comment, or check Composio has correct Instagram account linked |
| DM not being sent | Check OpenRouter account has credits |

**For detailed troubleshooting:** See `INSTAGRAM_AUTOMATION_SETUP_GUIDE.md` section 4.2

---

## ✨ Optional: Run Laravel Migration Now

If you have PHP installed on your Mac:
```bash
cd /path/to/vatan-ai-v500
php artisan migrate
```

**Or wait:** Migration runs automatically when deployed to Cloudiwa production.

---

## 🎉 Success Indicators

After activating, you should see within 24 hours:

✅ Instagram comments appearing in database  
✅ DMs being sent to followers  
✅ Dashboard stats updating  
✅ Logs showing successful executions  

---

## 📞 Summary

**Current Status:** All components ready, awaiting final n8n configuration  
**Time to Production:** 30-45 minutes  
**Monthly Cost:** ~$5-10 (OpenRouter only)  
**Iran Accessible:** ✅ Yes (via Composio)  

---

## Next Actions

1. ✅ Read this checklist → **Done**
2. ⏳ Import workflow into n8n → **Start here**
3. ⏳ Configure credentials
4. ⏳ Test & activate
5. ⏳ Monitor first execution
6. 🎉 Production live!

**You've got this!** 🚀

---

**Questions?** Refer to:
- Quick commands: `QUICK_REFERENCE.md`
- Detailed guide: `INSTAGRAM_AUTOMATION_SETUP_GUIDE.md`
- Architecture: Project docs in Claude

Good luck! 🎯
