import subprocess

def run_cmd(cmd):
    result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
    print(f"--- {cmd} ---\n{result.stdout}\n{result.stderr}")

run_cmd("git config --get remote.origin.url")
run_cmd("git fetch https://github.com/hamidnoshady/eshobe-ecommerce-wp-theme.git main")
run_cmd("git merge FETCH_HEAD")
