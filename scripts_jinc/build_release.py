import os
import zipfile

def is_ignored(path, base_path):
    rel_path = os.path.relpath(path, base_path)
    parts = rel_path.split(os.sep)
    
    ignored_dirs = ['.git', '.agents', 'tests', 'vendor', 'scripts_jinc', '.phpunit.cache', 'docs', '.vscode']
    
    for part in parts:
        if part in ignored_dirs:
            return True
            
    filename = os.path.basename(path)
    if filename == 'phpunit.xml' or filename.startswith('composer.'):
        return True
    if filename.endswith('.zip'):
        return True
        
    return False

def build_release():
    base_path = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
    zip_filename = os.path.join(base_path, 'wp-acessivel-jinc-v1.0.0.zip')
    
    with zipfile.ZipFile(zip_filename, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(base_path):
            # Prune ignored directories
            dirs[:] = [d for d in dirs if not is_ignored(os.path.join(root, d), base_path)]
            
            for file in files:
                file_path = os.path.join(root, file)
                if not is_ignored(file_path, base_path):
                    arcname = os.path.join('wp-acessivel-jinc', os.path.relpath(file_path, base_path))
                    zipf.write(file_path, arcname)
                    
    print(f"Build success! Saved to: {zip_filename}")

if __name__ == "__main__":
    build_release()
