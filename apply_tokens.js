const fs = require('fs');
const path = require('path');

const filesToProcess = [
    'application/views/pages/home/awal.php',
    'application/views/pages/home/tab_perumahan.php',
    'application/views/pages/home/tab_kawasan.php',
    'application/views/pages/home/tab_pertanahan.php',
    'application/views/pages/home/tab_pengembang.php',
    'application/views/pages/home/tab_bankdata.php',
    'application/views/layouts/footer.php'
];

const replacements = [
    { find: /text-\[\#0f2a30\]/g, replace: 'text-[color:var(--portal-text)]' },
    { find: /text-\[\#5a8a92\]/g, replace: 'text-[color:var(--portal-text-muted)]' },
    { find: /border:\s*1px\s*solid\s*rgba\(0,\s*80,\s*95,\s*0\.08\)/g, replace: 'border: 1px solid var(--portal-border)' },
    { find: /box-shadow:\s*0\s*1px\s*2px\s*0\s*rgba\(0,\s*0,\s*0,\s*0\.05\)/g, replace: 'box-shadow: var(--portal-shadow)' },
    { find: /color:\s*\#0f2a30/g, replace: 'color: var(--portal-icon)' },
    { find: /bg-\[\#0a1a1f\]\/8/g, replace: 'bg-[color:var(--portal-skeleton)]' },
    
    // Additional possibilities for buttons
    { find: /bg-\[\#0f2a30\]\/5/g, replace: 'bg-[color:var(--portal-btn-bg)]' },
    { find: /border-\[\#0f2a30\]\/10/g, replace: 'border-[color:var(--portal-btn-border)]' }
];

let changedFiles = 0;

filesToProcess.forEach(relPath => {
    const fullPath = path.join(__dirname, relPath);
    if (!fs.existsSync(fullPath)) {
        console.log(`[SKIP] File not found: ${relPath}`);
        return;
    }
    
    let content = fs.readFileSync(fullPath, 'utf8');
    let original = content;
    
    replacements.forEach(r => {
        content = content.replace(r.find, r.replace);
    });
    
    if (content !== original) {
        fs.writeFileSync(fullPath, content, 'utf8');
        console.log(`[SUCCESS] Modified: ${relPath}`);
        changedFiles++;
    } else {
        console.log(`[NO CHANGES] Nothing to replace in: ${relPath}`);
    }
});

console.log(`Done. Modified ${changedFiles} file(s).`);
