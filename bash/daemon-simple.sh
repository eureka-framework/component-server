#!/bin/bash

#~ Global daemon config
DAEMON_PID_PATH=${DAEMON_PID_PATH:-"/data/tmp/daemon/pid"}

if [ ! -d ${DAEMON_PID_PATH} ]; then
    mkdir -p "${DAEMON_PID_PATH}"
    test -d ${DAEMON_PID_PATH} || exit 1 # Test if exists after try create full path
fi

DAEMON_PID="${DAEMON_PID_PATH}/${DAEMON_NAME}.pid"
DAEMON_USER="$( id --user --name )" # Get current user

PATH="/sbin:/bin:/usr/sbin:/usr/bin" #do not touch

test -x ${DAEMON_SCRIPT} || exit 0

#~ Case for daemon argument (start|restart|stop|force-stop|status)
case "$1" in

    start|stop)
        d_${1}
        ;;

    restart)
        d_stop
        d_start
        ;;

    force-stop)
        d_stop
        killall -q ${DAEMON_NAME} || true
        sleep 2
        killall -q -9 ${DAEMON_NAME} || true
        ;;

    status)
        status_of_proc -p "${DAEMON_PID}" "${DAEMON_NAME}" "${DAEMON_NAME}" && exit 0 || exit $?
        ;;

    *)

    echo "Usage: ${PROJECT_DIR}/bin/daemon/${DAEMON_NAME} {start|stop|force-stop|restart|status}"
    exit 1
    ;;
esac

exit 0
