const fs = require('fs');
const path = require('path');

function checkIds(jsPath, htmlPath) {
  const jsCode = fs.readFileSync(path.join(__dirname, '..', jsPath), 'utf8');
  const htmlCode = fs.readFileSync(path.join(__dirname, '..', htmlPath), 'utf8');
  
  const idRegex = /document\.getElementById\(['"]([^'"]+)['"]\)/g;
  const ids = new Set();
  let match;
  while ((match = idRegex.exec(jsCode)) !== null) {
    ids.add(match[1]);
  }

  console.log(`Checking ${jsPath} against ${htmlPath}: Found ${ids.size} unique element IDs.`);
  let missing = [];
  for (const id of ids) {
    if (!htmlCode.includes(`id="${id}"`) && !htmlCode.includes(`id='${id}'`)) {
      missing.push(id);
    }
  }

  if (missing.length === 0) {
    console.log(`PASS: All ${ids.size} IDs in ${jsPath} exist in ${htmlPath}!`);
  } else {
    console.error(`FAIL: Missing IDs in ${htmlPath}:`, missing);
  }
  return missing.length === 0;
}

const pubOk = checkIds('public/app.js', 'public/index.html');
const admOk = checkIds('admin/admin.js', 'admin/index.html');

if (!pubOk || !admOk) {
  process.exit(1);
} else {
  console.log('DOM consistency check completely PASSED.');
}
